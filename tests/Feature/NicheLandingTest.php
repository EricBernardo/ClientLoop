<?php

namespace Tests\Feature;

use App\Enums\CompanyVertical;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class NicheLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_without_a_remembered_niche_offers_pet_and_automotive(): void
    {
        $this->get('/')
            ->assertSee(route('landing.pet'), false)
            ->assertSee(route('landing.automotive'), false)
            ->assertDontSee(route('register'), false);

        $this->get('/home')
            ->assertSee('Pet shop')
            ->assertSee('Serviços automotivos');
    }

    public function test_invalid_remembered_niche_shows_the_choice(): void
    {
        $this->withCookie(CompanyVertical::RememberedNicheCookie, 'clinica')
            ->get('/home')
            ->assertSee(route('landing.pet'), false)
            ->assertSee(route('landing.automotive'), false);
    }

    public function test_visiting_pet_remembers_the_niche_and_home_redirects_there(): void
    {
        $this->get(route('landing.pet'))
            ->assertSee('Sua agenda, seus pets e seus pacotes no mesmo lugar.')
            ->assertCookie(CompanyVertical::RememberedNicheCookie, CompanyVertical::PetShop->value);

        $this->withCookie(CompanyVertical::RememberedNicheCookie, CompanyVertical::PetShop->value)
            ->get('/home')
            ->assertRedirect(route('landing.pet'));

        $this->withCookie(CompanyVertical::RememberedNicheCookie, CompanyVertical::PetShop->value)
            ->get('/')
            ->assertRedirect(route('landing.pet'));
    }

    public function test_visiting_automotive_after_pet_redirects_home_to_automotive(): void
    {
        $this->withCookie(CompanyVertical::RememberedNicheCookie, CompanyVertical::PetShop->value)
            ->get(route('landing.automotive'))
            ->assertSee('Oficina com ordem de serviço, recibo e caixa.')
            ->assertCookie(CompanyVertical::RememberedNicheCookie, CompanyVertical::Automotive->value);

        $this->withCookie(CompanyVertical::RememberedNicheCookie, CompanyVertical::Automotive->value)
            ->get('/home')
            ->assertRedirect(route('landing.automotive'));
    }

    public function test_register_with_a_remembered_niche_locks_the_company_vertical(): void
    {
        Config::set('clientloop.require_email_verification', false);

        $this->withCookie(CompanyVertical::RememberedNicheCookie, CompanyVertical::PetShop->value)
            ->get(route('register'))
            ->assertDontSee('<select id="vertical"', false)
            ->assertSee('Tipo de negócio: Pet shop', false);

        $this->withCookie(CompanyVertical::RememberedNicheCookie, CompanyVertical::PetShop->value)
            ->post(route('register.store'), [
                'company_name' => 'Pet da Ana',
                'vertical' => CompanyVertical::Automotive->value,
                'name' => 'Ana',
                'email' => 'ana-pet@clientloop.test',
                'password' => 'password-password',
                'password_confirmation' => 'password-password',
            ])
            ->assertRedirect();

        $user = User::where('email', 'ana-pet@clientloop.test')->firstOrFail();

        $this->assertSame(CompanyVertical::PetShop, $user->company->vertical);
    }
}
