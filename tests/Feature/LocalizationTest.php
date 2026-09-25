<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_validation_messages_are_available_in_portuguese(): void
    {
        app()->setLocale('pt_BR');

        $this->assertSame('Preencha o campo serviço.', __('validation.required', ['attribute' => 'serviço']));
    }
}
