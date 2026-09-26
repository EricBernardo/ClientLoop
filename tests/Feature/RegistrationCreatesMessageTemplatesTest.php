<?php

namespace Tests\Feature;

use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\DefaultMessageTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RegistrationCreatesMessageTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_default_message_templates_for_the_company(): void
    {
        Config::set('clientloop.require_email_verification', false);

        $email = 'modelos@clientloop.test';

        $this->post(route('register.store'), [
            'company_name' => 'Pet Shop Modelos',
            'name' => 'Paula',
            'email' => $email,
            'password' => 'password-password',
            'password_confirmation' => 'password-password',
        ])->assertRedirect('/admin');

        $user = User::where('email', $email)->firstOrFail();
        $defaults = app(DefaultMessageTemplateService::class)->defaults();

        $this->assertCount(count($defaults), MessageTemplate::withoutGlobalScopes()->where('company_id', $user->company_id)->get());

        foreach ($defaults as $template) {
            $this->assertDatabaseHas('message_templates', [
                'company_id' => $user->company_id,
                'name' => $template['name'],
                'type' => $template['type'],
                'body' => $template['body'],
                'active' => true,
            ]);
        }
    }
}
