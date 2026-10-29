<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_messages_are_available_in_portuguese(): void
    {
        app()->setLocale('pt_BR');

        $this->assertSame('Preencha o campo serviço.', __('validation.required', ['attribute' => 'serviço']));
        $this->assertSame('A confirmação de senha não confere.', __('validation.confirmed', ['attribute' => 'senha']));
    }

    public function test_registration_password_confirmation_error_is_in_portuguese(): void
    {
        $this->from(route('register'))->post(route('register.store'), [
            'company_name' => 'Pet da Ana',
            'vertical' => 'pet_shop',
            'name' => 'Ana',
            'email' => 'ana@clientloop.test',
            'password' => 'password-password',
            'password_confirmation' => 'outra-senha-diferente',
        ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['password' => 'A confirmação de senha não confere.']);
    }
}
