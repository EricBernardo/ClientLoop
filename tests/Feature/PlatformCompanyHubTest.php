<?php

namespace Tests\Feature;

use App\Filament\Super\Resources\Companies\Pages\ViewCompany;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformCompanyHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_sees_company_overview_details(): void
    {
        [$admin, $company] = $this->platformCompanyWithCustomer();

        $this->actingAs($admin);
        Filament::setCurrentPanel('super');

        Livewire::test(ViewCompany::class, ['record' => $company->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('patinhas@example.test')
            ->assertSee('Ana patinhas')
            ->assertSee('Usuários')
            ->assertSee('Clientes')
            ->assertSee('Agendamentos')
            ->assertSee('Tarefas de contato')
            ->assertSee('Campanhas')
            ->assertSee('Imports');
    }

    public function test_superadmin_can_permanently_delete_a_company_and_cascade_tenant_data(): void
    {
        [$admin, $company] = $this->platformCompanyWithCustomer();
        $ownerId = $company->owner->id;
        $customerId = $company->customers()->first()->id;

        $this->actingAs($admin);
        Filament::setCurrentPanel('super');

        Livewire::test(ViewCompany::class, ['record' => $company->getRouteKey()])
            ->mountAction([
                'name' => 'delete',
                'context' => ['recordKey' => $company->getKey()],
            ])
            ->assertMountedActionModalSee('Excluir «'.$company->name.'» permanentemente')
            ->assertMountedActionModalSee('dados de «'.$company->name.'»')
            ->callMountedAction()
            ->assertHasNoActionErrors()
            ->assertRedirect(route('filament.super.resources.companies.index'));

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseMissing('customers', ['id' => $customerId]);
        $this->assertDatabaseMissing('company_subscriptions', ['company_id' => $company->id]);

        $owner = User::query()->findOrFail($ownerId);
        $this->assertNull($owner->company_id);
    }

    public function test_non_superadmin_cannot_access_platform_company_pages(): void
    {
        $plan = Plan::create(['name' => 'Teste', 'contact_limit' => 10, 'task_limit' => 10, 'is_default' => true]);
        $company = $this->shop($plan, 'patinhas', 'trial', now()->addDays(5));
        $owner = $company->owner;

        $this->actingAs($owner);

        $this->get('/platform/companies/'.$company->getRouteKey())->assertForbidden();
        $this->get('/platform/companies/'.$company->getRouteKey().'/edit')->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function platformCompanyWithCustomer(): array
    {
        $plan = Plan::create(['name' => 'Teste', 'contact_limit' => 10, 'task_limit' => 10, 'is_default' => true]);
        $company = $this->shop($plan, 'patinhas', 'trial', now()->addDays(5));
        Customer::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Maria Cliente',
            'phone' => '5511999999999',
        ]);

        $admin = User::create([
            'company_id' => $company->id,
            'name' => 'Plataforma',
            'email' => 'platform@example.test',
            'password' => 'password-password',
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);

        return [$admin, $company->fresh(['owner', 'subscription.plan', 'customers'])];
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

        return $company->fresh(['owner']);
    }
}
