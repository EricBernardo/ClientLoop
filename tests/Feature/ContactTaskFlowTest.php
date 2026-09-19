<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\Plan;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\ContactTaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTaskFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_uses_whatsapp_link_and_opt_out_cancels_pending_work(): void
    {
        $plan = Plan::create(['name' => 'Trial', 'contact_limit' => 10, 'task_limit' => 10, 'is_default' => true]);
        $company = Company::create(['name' => 'Empresa', 'slug' => 'empresa', 'follow_up_days' => [1, 3, 7]]);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id]);
        $user = User::create(['company_id' => $company->id, 'name' => 'Ana', 'email' => 'ana@example.test', 'password' => 'password-password']);
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Maria', 'phone' => '5511999999999']);

        $this->actingAs($user);
        $task = app(ContactTaskService::class)->create($company, $customer, 'follow_up', now(), []);

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
        $template = MessageTemplate::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Volte', 'type' => 'recall', 'body' => 'Olá, {{cliente}}.']);
        $campaign = Campaign::withoutGlobalScopes()->create(['company_id' => $company->id, 'message_template_id' => $template->id, 'name' => 'Retornos', 'type' => 'recall']);

        $this->assertSame(1, app(CampaignService::class)->launch($campaign));
        $this->assertDatabaseHas('contact_tasks', ['customer_id' => $eligible->id, 'type' => 'recall', 'status' => 'pending']);
    }
}
