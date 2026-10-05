<?php

namespace Tests\Feature;

use App\Enums\CompanyVertical;
use App\Filament\Pages\Calendar;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\PetPackages\PetPackageResource;
use App\Filament\Resources\Pets\PetResource;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\MessageTemplate;
use App\Models\Plan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class AutomotiveVerticalTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_register_offer_pet_shop_and_automotive(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Serviços automotivos')
            ->assertSee('Ordem de serviço')
            ->assertSee('pet shops de banho e tosa', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Tipo de negócio')
            ->assertSee('Serviços automotivos')
            ->assertSee('Pet shop');
    }

    public function test_registration_without_vertical_asks_for_the_business_type(): void
    {
        Config::set('clientloop.require_email_verification', false);

        $this->from(route('register'))->post(route('register.store'), [
            'company_name' => 'Sem nicho',
            'name' => 'Ana',
            'email' => 'sem-nicho@clientloop.test',
            'password' => 'password-password',
            'password_confirmation' => 'password-password',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors(['vertical' => 'Escolha o tipo de negócio.']);

        $this->assertDatabaseMissing('users', ['email' => 'sem-nicho@clientloop.test']);
    }

    public function test_automotive_registration_skips_pet_shop_message_templates(): void
    {
        Config::set('clientloop.require_email_verification', false);

        $this->post(route('register.store'), [
            'company_name' => 'Oficina do Zé',
            'vertical' => 'automotive',
            'name' => 'José',
            'email' => 'oficina@clientloop.test',
            'password' => 'password-password',
            'password_confirmation' => 'password-password',
        ])->assertRedirect();

        $user = User::where('email', 'oficina@clientloop.test')->firstOrFail();

        $this->assertSame(CompanyVertical::Automotive, $user->company->vertical);
        $this->assertSame(0, MessageTemplate::withoutGlobalScopes()->where('company_id', $user->company_id)->count());
    }

    public function test_automotive_company_cannot_open_pet_shop_pages(): void
    {
        [, $user] = $this->company(CompanyVertical::Automotive);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        $this->assertFalse(PetResource::canAccess());
        $this->assertFalse(PetPackageResource::canAccess());
        $this->assertFalse(Calendar::canAccess());

        $this->get('/admin/pets')->assertForbidden();
        $this->get('/admin/pet-packages')->assertForbidden();
        $this->get('/admin/calendar')->assertForbidden();
        $this->get('/admin/how-to-use')->assertForbidden();
    }

    public function test_pet_shop_company_still_opens_pets_and_labels_customers_as_responsaveis(): void
    {
        [, $user] = $this->company(CompanyVertical::PetShop);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        $this->assertTrue(PetResource::canAccess());
        $this->assertSame('Responsáveis', CustomerResource::getNavigationLabel());
    }

    public function test_automotive_customer_list_uses_cliente_and_service_form_skips_duration(): void
    {
        [, $user] = $this->company(CompanyVertical::Automotive);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        $this->assertSame('Clientes', CustomerResource::getNavigationLabel());

        Livewire::test(CreateService::class)
            ->fillForm([
                'name' => 'Alinhamento',
                'suggested_price' => '120,00',
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'company_id' => $user->company_id,
            'name' => 'Alinhamento',
            'suggested_price' => '120.00',
        ]);
    }

    public function test_automotive_company_cannot_download_the_pet_shop_import_template(): void
    {
        [, $user] = $this->company(CompanyVertical::Automotive);

        $this->actingAs($user)
            ->get(route('imports.template', ['type' => 'customers']))
            ->assertForbidden();
    }

    public function test_public_booking_is_unavailable_for_an_automotive_company(): void
    {
        [$company] = $this->company(CompanyVertical::Automotive);
        $company->forceFill(['public_booking_token' => 'oficina-token', 'status' => 'active'])->save();

        $this->get(route('booking.show', 'oficina-token'))->assertNotFound();
        $this->post(route('booking.store', 'oficina-token'), [])->assertNotFound();
    }

    /** @return array{Company, User} */
    private function company(CompanyVertical $vertical): array
    {
        $plan = Plan::create(['name' => 'Teste '.$vertical->value, 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => true]);
        $company = Company::create([
            'name' => 'Empresa '.$vertical->value,
            'slug' => $vertical->value.'-'.fake()->unique()->slug(),
            'status' => 'active',
            'vertical' => $vertical,
        ]);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Dono',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password-password',
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        return [$company, $user];
    }
}
