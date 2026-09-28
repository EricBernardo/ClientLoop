<?php

namespace Tests\Feature;

use App\Filament\Super\Resources\Companies\Pages\EditCompany;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\UsageRecord;
use App\Models\User;
use App\Support\SignupMonitor;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformSignupMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_separate_trials_ending_soon_unused_shops_and_full_quotas(): void
    {
        $plan = Plan::create(['name' => 'Teste', 'contact_limit' => 2, 'task_limit' => 5, 'is_default' => true]);
        $ending = $this->shop($plan, 'ending', 'trial', now()->addDays(2));
        Customer::withoutGlobalScopes()->create(['company_id' => $ending->id, 'name' => 'João', 'phone' => '5511888888888']);
        $this->shop($plan, 'unused', 'trial', now()->addDays(10));
        $started = $this->shop($plan, 'started', 'trial', now()->addDays(10));
        Customer::withoutGlobalScopes()->create(['company_id' => $started->id, 'name' => 'Maria', 'phone' => '5511999999999']);
        $full = $this->shop($plan, 'full', 'active', now()->addMonth());
        UsageRecord::withoutGlobalScopes()->create([
            'company_id' => $full->id,
            'period' => now()->format('Y-m'),
            'contacts_count' => 2,
            'tasks_count' => 1,
        ]);
        $old = $this->shop($plan, 'old', 'active', now()->addMonth());
        $old->forceFill(['created_at' => now()->subDays(8)])->saveQuietly();
        $this->shop($plan, 'suspended', 'suspended', now()->subDay());

        $counts = SignupMonitor::counts();

        $this->assertSame(3, $counts['trial']);
        $this->assertSame(1, $counts['ending']);
        $this->assertSame([$ending->id], SignupMonitor::trialEndingSoon(Company::query())->pluck('id')->all());
        $this->assertSame(1, $counts['unused']);
        $this->assertSame(1, $counts['quota']);
        $this->assertSame(1, $counts['suspended']);
        $this->assertSame(5, $counts['recent']);
    }

    public function test_platform_pages_show_the_owner_and_the_signup_summary(): void
    {
        $plan = Plan::create(['name' => 'Teste', 'contact_limit' => 10, 'task_limit' => 10, 'is_default' => true]);
        $company = $this->shop($plan, 'patinhas', 'trial', now()->addDays(2));
        $admin = User::create([
            'company_id' => $company->id,
            'name' => 'Plataforma',
            'email' => 'platform@example.test',
            'password' => 'password-password',
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($admin);
        Filament::setCurrentPanel('super');

        $this->get('/platform')->assertSee('Em teste')->assertSee('Teste acaba em 3 dias')->assertSee('Sem uso');
        Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
            ->assertSee('patinhas@example.test')
            ->assertSee('0/10 · 0/10');
    }

    public function test_extending_a_trial_adds_seven_days_and_keeps_the_company_on_trial(): void
    {
        $plan = Plan::create(['name' => 'Teste', 'contact_limit' => 10, 'task_limit' => 10, 'is_default' => true]);
        $company = $this->shop($plan, 'patinhas', 'trial', now()->addDay());
        UsageRecord::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'period' => now()->format('Y-m'),
            'contacts_count' => 1,
            'tasks_count' => 4,
        ]);

        SignupMonitor::extendTrial($company);

        $endsAt = CompanySubscription::withoutGlobalScopes()->where('company_id', $company->id)->first()->ends_at;
        $this->assertSame(now()->addDays(8)->toDateString(), $endsAt->toDateString());
        $this->assertSame('trial', $company->fresh()->status);
        $this->assertSame('1/10 · 4/10', SignupMonitor::usageLabel($company->fresh()->load(['subscription.plan', 'currentUsage'])));
    }

    private function shop(Plan $plan, string $slug, string $status, \DateTimeInterface $endsAt): Company
    {
        $company = Company::create(['name' => $slug, 'slug' => $slug, 'status' => $status]);
        CompanySubscription::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'starts_at' => now()->subDays(10),
            'ends_at' => $endsAt,
        ]);
        User::create([
            'company_id' => $company->id,
            'name' => 'Ana '.$slug,
            'email' => $slug.'@example.test',
            'password' => 'password-password',
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        return $company;
    }
}
