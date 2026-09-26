<?php

namespace Tests\Feature;

use App\Jobs\ProcessCsvImport;
use App\Models\Appointment;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\ImportRun;
use App\Models\MessageTemplate;
use App\Models\Pet;
use App\Models\Plan;
use App\Models\Service;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\ContactTaskService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_panel_renders_the_notification_bell(): void
    {
        [$company, $user] = $this->shop();
        $company->forceFill(['setup_wizard_completed_at' => now()])->save();
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('fi-topbar-database-notifications-btn', false);
    }

    public function test_tutor_confirmation_notifies_the_shop_once_and_not_another_company(): void
    {
        [$company, $user] = $this->shop();
        $other = User::create(['company_id' => Company::create(['name' => 'Outra', 'slug' => 'outra'])->id, 'name' => 'Outra', 'email' => 'outra@example.test', 'password' => 'password-password']);
        $superAdmin = User::create(['company_id' => $company->id, 'name' => 'Super', 'email' => 'super@example.test', 'password' => 'password-password', 'is_super_admin' => true]);
        $appointment = $this->appointment($company, 'confirm-token');

        $this->post('/confirm/confirm-token')->assertRedirect();
        $this->post('/confirm/confirm-token')->assertRedirect();

        $this->assertSame('confirmed', $appointment->fresh()->status);
        $this->assertSame(['Presença confirmada'], $user->notifications->map(fn ($notification): string => $notification->data['title'])->all());
        $this->assertSame('Thor (Maria) em 29/09/2026 10:00.', $user->notifications->first()->data['body']);
        $this->assertSame(0, $other->notifications()->count());
        $this->assertSame(0, $superAdmin->notifications()->count());
    }

    public function test_tutor_cancellation_notifies_the_shop(): void
    {
        [$company, $user] = $this->shop();
        $appointment = $this->appointment($company, 'cancel-token');

        $this->post('/confirm/cancel-token/cancel')->assertRedirect();

        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertSame(['Horário cancelado'], $user->notifications->map(fn ($notification): string => $notification->data['title'])->all());
        $this->assertSame('Thor (Maria) em 29/09/2026 10:00.', $user->notifications->first()->data['body']);
    }

    public function test_exhausted_task_quota_notifies_the_shop_once_per_month(): void
    {
        [$company, $user] = $this->shop(taskLimit: 1);
        UsageRecord::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'period' => now()->format('Y-m'),
            'tasks_count' => 1,
        ]);
        $first = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Maria', 'phone' => '5511999999991']);
        $second = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'João', 'phone' => '5511999999992']);

        $this->assertNull(app(ContactTaskService::class)->create($company, $first, 'recall', now(), []));
        $this->assertNull(app(ContactTaskService::class)->create($company, $second, 'recall', now(), []));

        $this->assertSame(['Cota de tarefas esgotada'], $user->notifications->map(fn ($notification): string => $notification->data['title'])->all());
        $this->assertSame('Confirmações e retornos não serão criados até o próximo mês.', $user->notifications->first()->data['body']);
        $this->assertNotNull(UsageRecord::withoutGlobalScopes()->where('company_id', $company->id)->value('task_quota_notified_at'));
    }

    public function test_opted_out_customer_does_not_notify_the_shop(): void
    {
        [$company, $user] = $this->shop();
        $customer = Customer::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Maria',
            'phone' => '5511999999993',
            'opted_out_at' => now(),
        ]);

        $this->assertNull(app(ContactTaskService::class)->create($company, $customer, 'recall', now(), []));

        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_completed_import_notifies_the_shop_with_the_result(): void
    {
        Storage::fake('local');
        [$company, $user] = $this->shop();
        Storage::disk('local')->put('imports/clientes.csv', "responsible_name,responsible_phone,pet_name\nMaria,11988887777,Luna\n");
        $run = ImportRun::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'type' => 'customers',
            'path' => 'imports/clientes.csv',
            'mapping' => [
                'responsible_name' => 'responsible_name',
                'responsible_phone' => 'responsible_phone',
                'pet_name' => 'pet_name',
            ],
        ]);

        ProcessCsvImport::dispatchSync($run->id);

        $this->assertSame('completed', $run->fresh()->status);
        $this->assertSame('Importação concluída', $user->notifications->first()->data['title']);
        $this->assertSame('1 responsável criado, 0 atualizados.', $user->notifications->first()->data['body']);
    }

    public function test_missing_import_file_notifies_the_shop(): void
    {
        Storage::fake('local');
        [$company, $user] = $this->shop();
        $run = ImportRun::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'type' => 'customers',
            'path' => 'imports/arquivo-ausente.csv',
        ]);

        ProcessCsvImport::dispatchSync($run->id);

        $this->assertSame('failed', $run->fresh()->status);
        $this->assertSame('Importação falhou', $user->notifications->first()->data['title']);
        $this->assertSame('O arquivo enviado não está disponível para processamento. Envie o CSV novamente.', $user->notifications->first()->data['body']);
    }

    public function test_launched_campaign_notifies_the_shop(): void
    {
        [$company, $user] = $this->shop();
        Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'João', 'phone' => '5511933333333', 'next_return_at' => now()->subDay()]);
        $template = MessageTemplate::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Volte', 'type' => 'recall', 'body' => 'Olá, {{responsavel}}.']);
        $campaign = Campaign::withoutGlobalScopes()->create(['company_id' => $company->id, 'message_template_id' => $template->id, 'name' => 'Retornos', 'type' => 'recall']);

        $this->assertSame(1, app(CampaignService::class)->launch($campaign));

        $this->assertSame('Campanha ativada', $user->notifications->first()->data['title']);
        $this->assertSame('Retornos: 1 tarefa criada na fila de contatos.', $user->notifications->first()->data['body']);
    }

    /** @return array{Company, User} */
    private function shop(int $taskLimit = 10): array
    {
        $plan = Plan::create(['name' => 'Trial', 'contact_limit' => 10, 'task_limit' => $taskLimit, 'is_default' => true]);
        $company = Company::create(['name' => 'Patinhas', 'slug' => 'patinhas-'.fake()->unique()->numerify('####'), 'timezone' => 'America/Sao_Paulo']);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id]);
        $user = User::create(['company_id' => $company->id, 'name' => 'Ana', 'email' => fake()->unique()->safeEmail(), 'password' => 'password-password']);

        return [$company, $user];
    }

    private function appointment(Company $company, string $token): Appointment
    {
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Maria', 'phone' => '5511999999999']);
        $pet = Pet::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'name' => 'Thor']);
        $service = Service::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Banho', 'duration_minutes' => 60]);

        return Appointment::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'scheduled_at' => Carbon::parse('2026-09-29 10:00:00', 'America/Sao_Paulo'),
            'duration_minutes' => 60,
            'status' => 'scheduled',
            'confirmation_token' => $token,
        ]);
    }
}
