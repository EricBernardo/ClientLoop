<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\User;
use App\Services\ContactTaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_resource_is_registered_and_scoped_to_company(): void
    {
        [$company, $user] = $this->company();
        [$other] = $this->company();
        $this->actingAs($user);

        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Maria', 'phone' => '5511980000099']);
        ActivityLog::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'event' => 'customer.opted_out',
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'properties' => ['note' => 'Pediu para parar'],
        ]);
        ActivityLog::withoutGlobalScopes()->create([
            'company_id' => $other->id,
            'event' => 'customer.opted_out',
            'subject_type' => Customer::class,
            'subject_id' => 999,
            'properties' => [],
        ]);

        $this->assertSame('Histórico de ações', ActivityLogResource::getNavigationLabel());
        $this->assertFalse(ActivityLogResource::canCreate());
        $this->assertSame(1, ActivityLog::query()->count());
        $this->assertStringContainsString('/activity-logs', ActivityLogResource::getUrl('index'));

        if (extension_loaded('intl')) {
            $this->get('/admin/activity-logs')
                ->assertOk()
                ->assertSee('Histórico de ações')
                ->assertSee('Opt-out de contato')
                ->assertSee('Pediu para parar');
        }
    }

    public function test_completing_opt_out_creates_activity_log(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'João', 'phone' => '5511980000098']);

        app(ContactTaskService::class)->optOut($customer, 'Não ligar mais');

        $this->assertDatabaseHas('activity_logs', [
            'company_id' => $company->id,
            'event' => 'customer.opted_out',
            'subject_id' => $customer->id,
        ]);
    }

    /** @return array{Company, User} */
    private function company(): array
    {
        $plan = Plan::create(['name' => 'Teste', 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => true]);
        $company = Company::create(['name' => 'Empresa '.fake()->uuid(), 'slug' => fake()->unique()->slug(), 'status' => 'active']);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active']);
        $user = User::create(['company_id' => $company->id, 'name' => 'Usuária', 'email' => fake()->unique()->safeEmail(), 'password' => 'password-password', 'email_verified_at' => now()]);

        return [$company, $user];
    }
}
