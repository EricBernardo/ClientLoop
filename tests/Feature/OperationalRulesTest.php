<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\Plan;
use App\Models\User;
use App\Services\ContactTaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
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
        [, $user] = $this->company();
        $this->actingAs($user);

        $this->get('/app/campaigns')->assertOk()->assertSee('Campanhas');
        $this->get('/app/imports')->assertOk()->assertSee('Importar dados');
        $this->get('/app/operations-board')->assertOk()->assertSee('Agenda e funil');
    }

    /** @return array{Company, User} */
    private function company(): array
    {
        $plan = Plan::create(['name' => 'Teste', 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => true]);
        $company = Company::create(['name' => 'Empresa teste '.fake()->uuid(), 'slug' => fake()->unique()->slug(), 'status' => 'active', 'follow_up_days' => [1, 3, 7]]);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active']);
        $user = User::create(['company_id' => $company->id, 'name' => 'Usuária', 'email' => fake()->unique()->safeEmail(), 'password' => 'password-password']);
        $user->forceFill(['email_verified_at' => now()])->save();

        return [$company, $user];
    }
}
