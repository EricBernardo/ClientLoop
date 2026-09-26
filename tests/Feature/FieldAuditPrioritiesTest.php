<?php

namespace Tests\Feature;

use App\Filament\Resources\Campaigns\CampaignResource;
use App\Filament\Widgets\CompanyOverview;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\PackageOffer;
use App\Models\PackageOfferItem;
use App\Models\Pet;
use App\Models\PetPackage;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FieldAuditPrioritiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_package_and_subscription_columns_are_gone(): void
    {
        $this->assertFalse(Schema::hasColumn('package_offers', 'service_id'));
        $this->assertFalse(Schema::hasColumn('pet_packages', 'service_id'));
        $this->assertTrue(Schema::hasColumn('company_subscriptions', 'ends_at'));
    }

    public function test_campaign_recipients_contact_task_foreign_key_exists(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('campaign_recipients'));
        $this->assertTrue($foreignKeys->contains(fn (array $key): bool => in_array('contact_task_id', $key['columns'], true)));
    }

    public function test_package_offer_and_pet_package_work_without_legacy_service_id(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        $service = Service::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Banho', 'duration_minutes' => 60]);
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Ana', 'phone' => '5511999000001']);
        $pet = Pet::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'name' => 'Thor']);
        $offer = PackageOffer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => '2 banhos', 'credits' => 2, 'active' => true]);
        PackageOfferItem::withoutGlobalScopes()->create(['company_id' => $company->id, 'package_offer_id' => $offer->id, 'service_id' => $service->id, 'position' => 1]);
        PackageOfferItem::withoutGlobalScopes()->create(['company_id' => $company->id, 'package_offer_id' => $offer->id, 'service_id' => $service->id, 'position' => 2]);

        $package = PetPackage::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'pet_id' => $pet->id,
            'package_offer_id' => $offer->id,
            'payment_status' => 'paid',
            'purchased_at' => today(),
        ]);

        $this->assertSame(2, $package->fresh()->items()->count());
        $this->assertSame(2, $package->fresh()->remaining_credits);
        $this->assertTrue($package->fresh()->isUsableFor($pet->id, $service->id));
    }

    public function test_company_overview_includes_plan_usage_stat(): void
    {
        [, $user] = $this->company();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        $stats = (new class extends CompanyOverview
        {
            public function exposed(): array
            {
                return $this->getStats();
            }
        })->exposed();

        $labels = collect($stats)->map(fn ($stat) => $stat->getLabel())->all();
        $this->assertContains('Uso do plano', $labels);
    }

    public function test_active_campaign_can_be_completed(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));
        $template = MessageTemplate::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Reativar',
            'type' => 'reactivation',
            'body' => 'Oi {{responsavel}}',
            'active' => true,
        ]);
        $campaign = Campaign::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Reativar inativos',
            'type' => 'reactivation',
            'status' => 'active',
            'message_template_id' => $template->id,
            'filters' => [],
        ]);

        $source = file_get_contents(app_path('Filament/Resources/Campaigns/CampaignResource.php'));
        $this->assertStringContainsString("Action::make('complete')", $source);
        $this->assertStringContainsString('Encerrar campanha', $source);
        $this->assertStringContainsString('Com retorno previsto nos últimos X meses', $source);
        $this->assertStringContainsString('meses de reativação', $source);

        $campaign->update(['status' => 'completed']);
        $this->assertSame('completed', $campaign->fresh()->status);
        $this->assertSame(CampaignResource::getModelLabel(), 'campanha');
    }

    /** @return array{Company, User} */
    private function company(): array
    {
        $plan = Plan::create(['name' => 'Teste', 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => true]);
        $company = Company::create(['name' => 'Empresa Audit '.fake()->uuid(), 'slug' => fake()->unique()->slug(), 'status' => 'active']);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now()]);
        $user = User::create(['company_id' => $company->id, 'name' => 'Dona', 'email' => fake()->unique()->safeEmail(), 'password' => 'password-password']);
        $user->forceFill(['email_verified_at' => now()])->save();

        return [$company, $user];
    }
}
