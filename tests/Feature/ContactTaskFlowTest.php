<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\Pet;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\ContactTaskService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTaskFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_uses_whatsapp_link_and_opt_out_cancels_pending_work(): void
    {
        $plan = Plan::create(['name' => 'Trial', 'contact_limit' => 10, 'task_limit' => 10, 'is_default' => true]);
        $company = Company::create(['name' => 'Empresa', 'slug' => 'empresa']);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id]);
        $user = User::create(['company_id' => $company->id, 'name' => 'Ana', 'email' => 'ana@example.test', 'password' => 'password-password']);
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Maria', 'phone' => '5511999999999']);

        $this->actingAs($user);
        $task = app(ContactTaskService::class)->create($company, $customer, 'reactivation', now(), []);

        $this->assertNotNull($task);
        $this->assertStringContainsString('wa.me/5511999999999', $task->whatsappUrl());
        app(ContactTaskService::class)->complete($task, 'opt_out', 'Não quero mensagens');

        $this->assertNotNull($customer->fresh()->opted_out_at);
        $this->assertSame('completed', $task->fresh()->status);
        $this->assertSame(0, ContactTask::where('customer_id', $customer->id)->where('status', 'pending')->count());
    }

    public function test_global_scope_hides_other_company_customers(): void
    {
        $first = Company::create(['name' => 'A', 'slug' => 'a']);
        $second = Company::create(['name' => 'B', 'slug' => 'b']);
        $user = User::create(['company_id' => $first->id, 'name' => 'Ana', 'email' => 'scope@example.test', 'password' => 'password-password']);
        Customer::withoutGlobalScopes()->create(['company_id' => $first->id, 'name' => 'Visível', 'phone' => '5511911111111']);
        Customer::withoutGlobalScopes()->create(['company_id' => $second->id, 'name' => 'Oculta', 'phone' => '5511922222222']);
        $this->actingAs($user);
        $this->assertSame(['Visível'], Customer::query()->pluck('name')->all());
    }

    public function test_campaign_excludes_opted_out_customers_and_creates_manual_tasks(): void
    {
        $plan = Plan::create(['name' => 'Trial', 'contact_limit' => 10, 'task_limit' => 10, 'is_default' => true]);
        $company = Company::create(['name' => 'Campanha', 'slug' => 'campanha']);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id]);
        $eligible = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'João', 'phone' => '5511933333333', 'next_return_at' => now()->subDay()]);
        Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Paula', 'phone' => '5511944444444', 'next_return_at' => now()->subDay(), 'opted_out_at' => now()]);
        $template = MessageTemplate::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Volte', 'type' => 'recall', 'body' => 'Olá, {{responsavel}}.']);
        $campaign = Campaign::withoutGlobalScopes()->create(['company_id' => $company->id, 'message_template_id' => $template->id, 'name' => 'Retornos', 'type' => 'recall']);

        $this->assertSame(1, app(CampaignService::class)->launch($campaign));
        $this->assertDatabaseHas('contact_tasks', ['customer_id' => $eligible->id, 'type' => 'recall', 'status' => 'pending']);
    }

    public function test_first_no_response_on_confirmation_keeps_task_pending(): void
    {
        [$company, $user, $customer, $appointment] = $this->confirmationContext();
        $this->actingAs($user);
        $task = app(ContactTaskService::class)->create($company, $customer, 'confirmation', now(), [
            'appointment' => $appointment,
            'cycle_key' => 'appointment:'.$appointment->id,
        ]);

        app(ContactTaskService::class)->complete($task, 'no_response', 'Não atendeu');

        $task->refresh();
        $this->assertSame('pending', $task->status);
        $this->assertNull($task->outcome);
        $this->assertSame(1, $task->attempts()->count());
        $this->assertSame('scheduled', $appointment->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['event' => 'contact_task.no_response_retry', 'subject_id' => $task->id]);
    }

    public function test_second_no_response_on_confirmation_completes_task(): void
    {
        [$company, $user, $customer, $appointment] = $this->confirmationContext();
        $this->actingAs($user);
        $task = app(ContactTaskService::class)->create($company, $customer, 'confirmation', now(), [
            'appointment' => $appointment,
            'cycle_key' => 'appointment:'.$appointment->id,
        ]);

        app(ContactTaskService::class)->complete($task, 'no_response');
        app(ContactTaskService::class)->complete($task->fresh(), 'no_response');

        $this->assertSame('completed', $task->fresh()->status);
        $this->assertSame('no_response', $task->fresh()->outcome);
        $this->assertSame(2, $task->attempts()->count());
    }

    public function test_confirmed_after_first_no_response_marks_appointment_confirmed(): void
    {
        [$company, $user, $customer, $appointment] = $this->confirmationContext();
        $this->actingAs($user);
        $task = app(ContactTaskService::class)->create($company, $customer, 'confirmation', now(), [
            'appointment' => $appointment,
            'cycle_key' => 'appointment:'.$appointment->id,
        ]);

        app(ContactTaskService::class)->complete($task, 'no_response');
        app(ContactTaskService::class)->complete($task->fresh(), 'confirmed');

        $this->assertSame('completed', $task->fresh()->status);
        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_scheduled_outcome_completes_task_without_changing_linked_appointment(): void
    {
        [$company, $user, $customer, $appointment] = $this->confirmationContext();
        $this->actingAs($user);
        $task = app(ContactTaskService::class)->create($company, $customer, 'recall', now(), []);

        app(ContactTaskService::class)->complete($task, 'scheduled');

        $this->assertSame('completed', $task->fresh()->status);
        $this->assertSame('scheduled', $appointment->fresh()->status);
    }

    /** @return array{Company, User, Customer, Appointment} */
    private function confirmationContext(): array
    {
        $plan = Plan::create(['name' => 'Trial', 'contact_limit' => 50, 'task_limit' => 50, 'is_default' => true]);
        $company = Company::create(['name' => 'Confirmação', 'slug' => fake()->unique()->slug(), 'status' => 'active']);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active']);
        $user = User::create(['company_id' => $company->id, 'name' => 'Ana', 'email' => fake()->unique()->safeEmail(), 'password' => 'password-password', 'email_verified_at' => now()]);
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Maria', 'phone' => '5511999998888']);
        $pet = Pet::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'name' => 'Thor']);
        $service = Service::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Banho', 'duration_minutes' => 60]);
        $appointment = Appointment::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->next(Carbon::TUESDAY)->setTime(10, 0),
            'status' => 'scheduled',
        ]);

        return [$company, $user, $customer, $appointment];
    }
}
