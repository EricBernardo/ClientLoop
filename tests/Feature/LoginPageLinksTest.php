<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginPageLinksTest extends TestCase
{
    public function test_login_page_links_to_registration_and_home(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Cadastre-se')
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('Ir para a página inicial')
            ->assertSee('href="'.route('home').'"', false);
    }

    public function test_password_reset_page_hides_login_navigation_links(): void
    {
        $this->get('/admin/password-reset/request')
            ->assertOk()
            ->assertDontSee('Cadastre-se')
            ->assertDontSee('Ir para a página inicial');
    }
}
