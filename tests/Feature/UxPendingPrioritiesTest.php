<?php

namespace Tests\Feature;

use App\Filament\Pages\Calendar;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\ContactTasks\ContactTaskResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\PackageOffers\PackageOfferResource;
use App\Filament\Widgets\PlanUsageWidget;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Plan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UxPendingPrioritiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_usage_widget_and_navigation_groups(): void
    {
        [, $user] = $this->company();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        $this->assertTrue(PlanUsageWidget::canView());
        $this->assertSame('Operação', Calendar::getNavigationGroup());
        $this->assertSame('Operação', ContactTaskResource::getNavigationGroup());
        $this->assertSame('Operação', AppointmentResource::getNavigationGroup());
        $this->assertTrue(AppointmentResource::shouldRegisterNavigation());
        $this->assertSame('Lista de atendimentos', AppointmentResource::getNavigationLabel());
        $this->assertSame('Cadastros', CustomerResource::getNavigationGroup());
    }

    public function test_customer_opt_out_is_action_only_and_package_defaults_two_items(): void
    {
        $customerResource = file_get_contents(app_path('Filament/Resources/Customers/CustomerResource.php'));
        $packageResource = file_get_contents(app_path('Filament/Resources/PackageOffers/PackageOfferResource.php'));

        $this->assertStringNotContainsString("HourlyDateTimePicker::make('opted_out_at')", $customerResource);
        $this->assertStringContainsString('bloquearContato', $customerResource);
        $this->assertStringContainsString('->defaultItems(2)', $packageResource);
        $this->assertSame('Configurações', PackageOfferResource::getNavigationGroup());
    }

    public function test_filament_dashboard_title_is_portuguese(): void
    {
        app()->setLocale('pt_BR');

        $this->assertSame('Painel de Controle', __('filament-panels::pages/dashboard.title'));
    }

    public function test_calendar_mobile_list_markup_is_present(): void
    {
        [, $user] = $this->company();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        Livewire::test(Calendar::class)
            ->assertSee('pet-calendar__mobile', false)
            ->assertSee('Nenhum atendimento neste dia', false);
    }

    public function test_whatsapp_one_shot_action_exists_on_contact_queue(): void
    {
        $source = file_get_contents(app_path('Filament/Resources/ContactTasks/ContactTaskResource.php'));

        $this->assertStringContainsString("Action::make('whatsappRegistrar')", $source);
        $this->assertStringContainsString('WhatsApp e registrar', $source);
    }

    /** @return array{Company, User} */
    private function company(): array
    {
        $plan = Plan::create(['name' => 'Teste', 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => true]);
        $company = Company::create(['name' => 'Empresa UX '.fake()->uuid(), 'slug' => fake()->unique()->slug(), 'status' => 'active']);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active']);
        $user = User::create(['company_id' => $company->id, 'name' => 'Dona', 'email' => fake()->unique()->safeEmail(), 'password' => 'password-password']);
        $user->forceFill(['email_verified_at' => now()])->save();

        return [$company, $user];
    }
}
