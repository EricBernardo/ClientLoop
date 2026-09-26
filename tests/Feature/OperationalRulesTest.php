<?php

namespace Tests\Feature;

use App\Filament\Pages\Calendar;
use App\Filament\Resources\ContactTasks\ContactTaskResource;
use App\Jobs\ProcessCsvImport;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\ImportRun;
use App\Models\MessageTemplate;
use App\Models\Plan;
use App\Models\User;
use App\Services\ContactTaskService;
use App\Services\CsvImportService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_creates_confirmation_and_reactivation_only_once(): void
    {
        [$company] = $this->company();
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Cliente da agenda', 'phone' => '5511980000001']);
        $inactive = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Cliente inativo', 'phone' => '5511980000004', 'last_activity_at' => now()->subMonths(7)]);
        $scheduled = Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'scheduled_at' => now()->addHours(12), 'status' => 'scheduled']);

        Artisan::call('clientloop:generate-tasks');
        Artisan::call('clientloop:generate-tasks');

        $this->assertSame(1, ContactTask::withoutGlobalScopes()->where('appointment_id', $scheduled->id)->where('type', 'confirmation')->count());
        $this->assertSame(1, ContactTask::withoutGlobalScopes()->where('customer_id', $inactive->id)->where('type', 'reactivation')->count());
    }

    public function test_reschedule_cancels_old_contact_task_and_preserves_appointment(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Ana', 'phone' => '5511980000002']);
        $appointment = Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'scheduled_at' => now()->addDay(), 'status' => 'scheduled']);
        $task = app(ContactTaskService::class)->create($company, $customer, 'confirmation', now(), ['appointment' => $appointment, 'cycle_key' => 'appointment:'.$appointment->id]);

        app(ContactTaskService::class)->reschedule($appointment, now()->addDays(3));

        $this->assertSame('cancelled', $task->fresh()->status);
        $this->assertSame('scheduled', $appointment->fresh()->status);
        $this->assertTrue($appointment->fresh()->scheduled_at->isAfter(now()->addDays(2)));
    }

    public function test_unknown_template_variable_and_invalid_appointment_transition_are_rejected(): void
    {
        [$company] = $this->company();
        $this->expectException(InvalidArgumentException::class);
        MessageTemplate::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Inválido', 'type' => 'recall', 'body' => 'Olá {{nao_existe}}', 'active' => true]);
    }

    public function test_opt_in_requires_explicit_new_consent(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'João', 'phone' => '5511980000003', 'opted_out_at' => now()]);
        app(ContactTaskService::class)->optIn($customer, 'Autorizou por telefone em 19/09.');

        $this->assertNull($customer->fresh()->opted_out_at);
        $this->assertStringContainsString('Novo consentimento', $customer->fresh()->opt_out_note);
    }

    public function test_company_panel_exposes_operational_pages_in_portuguese(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Cliente da tela', 'phone' => '5511980000020']);
        Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'scheduled_at' => now()->addDay(), 'status' => 'scheduled']);

        $this->get('/admin/campaigns')->assertOk()->assertSee('Campanhas');
        $this->get('/admin/imports')
            ->assertOk()
            ->assertSee('Importar responsáveis e pets')
            ->assertDontSee('Agendamentos e histórico');
        $this->get('/admin/how-to-use')->assertOk()->assertSee('Organize seu pet shop, um banho de cada vez.')->assertSee('Cadastre os serviços')->assertSee('Cadastre os pets');
        $this->get('/admin/appointments')->assertOk()->assertSee('Data e horário')->assertSee('Situação')->assertDontSee('Valor potencial')->assertSee('Agendado');
    }

    public function test_contact_queue_is_sorted_by_due_date_when_opened(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        $first = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Cliente com vencimento antigo', 'phone' => '5511980000041']);
        $last = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Cliente com vencimento futuro', 'phone' => '5511980000042']);
        ContactTask::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $last->id, 'type' => 'recall', 'priority' => 'normal', 'due_at' => now()->addDay(), 'rendered_message' => 'Mensagem futura']);
        ContactTask::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $first->id, 'type' => 'recall', 'priority' => 'normal', 'due_at' => now()->subDay(), 'rendered_message' => 'Mensagem antiga']);

        $this->get('/admin/contact-tasks')
            ->assertOk()
            ->assertSeeInOrder(['Cliente com vencimento antigo', 'Cliente com vencimento futuro']);

        $this->assertTrue(ContactTaskResource::shouldRegisterNavigation());
    }

    public function test_import_history_shows_the_reason_for_each_rejected_line(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        ImportRun::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'type' => 'customers',
            'status' => 'completed',
            'path' => 'imports/example.csv',
            'errors' => [2 => 'Informe um telefone brasileiro válido.'],
        ]);

        $this->get('/admin/imports')->assertOk()->assertSee('Ver erros')->assertSee('Linha 2:')->assertSee('Informe um telefone brasileiro válido.')->assertSee('Responsáveis e pets');
    }

    public function test_missing_import_file_is_explained_without_exposing_a_technical_exception(): void
    {
        Storage::fake('local');
        [$company, $user] = $this->company();
        $run = ImportRun::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'type' => 'customers',
            'path' => 'imports/arquivo-ausente.csv',
        ]);

        ProcessCsvImport::dispatchSync($run->id);

        $this->assertSame('failed', $run->fresh()->status);
        $this->assertSame([
            'arquivo' => 'O arquivo enviado não está disponível para processamento. Envie o CSV novamente.',
        ], $run->fresh()->errors);
    }

    public function test_previous_missing_file_errors_are_shown_with_a_clear_message(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        ImportRun::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'type' => 'customers',
            'status' => 'failed',
            'path' => 'imports/arquivo-ausente.csv',
            'errors' => ['arquivo' => 'SplFileObject::__construct(/var/www/html/storage/app/private/imports/exemplo.csv): Failed to open stream: No such file or directory'],
        ]);

        $this->get('/admin/imports')
            ->assertOk()
            ->assertSee('O arquivo enviado não estava disponível para o processamento. Envie o CSV novamente.')
            ->assertDontSee('SplFileObject::__construct');
    }

    public function test_customer_import_accepts_the_utf8_bom_used_by_the_downloaded_template(): void
    {
        Storage::fake('local');
        [$company] = $this->company();
        Storage::disk('local')->put('imports/clientes.csv', "\xEF\xBB\xBFcustomer_name,phone\nAna Silva,(11) 99999-9999\n");

        $result = app(CsvImportService::class)->customers(
            $company,
            Storage::disk('local')->path('imports/clientes.csv'),
            ['name' => 'customer_name', 'phone' => 'phone'],
        );

        $this->assertSame(['created' => 1, 'updated' => 0, 'errors' => []], $result);
        $this->assertDatabaseHas('customers', ['company_id' => $company->id, 'name' => 'Ana Silva']);
    }

    public function test_public_site_explains_product_and_exposes_registration(): void
    {
        $this->get('/')->assertOk()->assertSee('Sua agenda, seus pets e seus pacotes no mesmo lugar.')->assertSee(route('register'))->assertSee('/images/clientloop-symbol.png');
        $this->get('/register')->assertOk()->assertSee('Crie sua conta');
        $this->get('/cadastro')->assertRedirect('/register');
    }

    public function test_email_verification_notice_is_branded_and_password_reset_is_available(): void
    {
        [$company, $user] = $this->company();
        $user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($user)
            ->get('/verify-email')
            ->assertOk()
            ->assertSee('Confirme seu e-mail')
            ->assertSee('Client')
            ->assertSee('Reenviar link')
            ->assertSee('Já confirmei — ir ao painel');

        auth()->logout();

        $this->get('/admin/password-reset/request')->assertOk();
    }

    public function test_calendar_defaults_to_day_mode(): void
    {
        [, $user] = $this->company();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        Livewire::test(Calendar::class)
            ->assertSet('mode', 'day');
    }

    /** @return array{Company, User} */
    private function company(): array
    {
        $plan = Plan::create(['name' => 'Teste', 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => true]);
        $company = Company::create(['name' => 'Empresa teste '.fake()->uuid(), 'slug' => fake()->unique()->slug(), 'status' => 'active']);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active']);
        $user = User::create(['company_id' => $company->id, 'name' => 'Usuária', 'email' => fake()->unique()->safeEmail(), 'password' => 'password-password']);
        $user->forceFill(['email_verified_at' => now()])->save();

        return [$company, $user];
    }
}
