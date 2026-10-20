<?php

namespace Tests\Feature;

use App\Enums\CompanyVertical;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
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

    public function test_pet_landing_shows_only_the_featured_videos(): void
    {
        Storage::fake('videos');
        Storage::disk('videos')->put('01-o-que-o-clientloop-faz.mp4', 'video');
        Storage::disk('videos')->put('05-marcar-um-horario.mp4', 'video');
        Storage::disk('videos')->put('06-confirmar-pelo-whatsapp.mp4', 'video');
        Storage::disk('videos')->put('02-horarios-e-servicos.mp4', 'video');

        $this->get(route('landing.pet'))
            ->assertSee('Veja a rotina em três vídeos.')
            ->assertSee('O que o ClientLoop faz')
            ->assertSee('Marcar um horário')
            ->assertSee('Confirmar pelo WhatsApp')
            ->assertSee('01-o-que-o-clientloop-faz.mp4', false)
            ->assertSee('05-marcar-um-horario.mp4', false)
            ->assertSee('06-confirmar-pelo-whatsapp.mp4', false)
            ->assertDontSee('Horários e serviços');
    }

    public function test_pet_landing_omits_a_featured_video_when_its_file_is_missing(): void
    {
        Storage::fake('videos');
        Storage::disk('videos')->put('01-o-que-o-clientloop-faz.mp4', 'video');
        Storage::disk('videos')->put('06-confirmar-pelo-whatsapp.mp4', 'video');

        $this->get(route('landing.pet'))
            ->assertSee('O que o ClientLoop faz')
            ->assertSee('Confirmar pelo WhatsApp')
            ->assertDontSee('Marcar um horário');
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
