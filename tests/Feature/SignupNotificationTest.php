<?php

namespace Tests\Feature;

use App\Enums\CompanyVertical;
use App\Models\Company;
use App\Models\User;
use App\Notifications\NewCompanyRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SignupNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_emails_the_configured_address_and_super_admins(): void
    {
        Notification::fake();
        Config::set('clientloop.require_email_verification', false);
        Config::set('clientloop.signup_notification_email', 'Dono@ClientLoop.test, platform@clientloop.test');
        User::create([
            'name' => 'Plataforma',
            'email' => 'platform@clientloop.test',
            'password' => 'password-password',
            'is_super_admin' => true,
            'role' => 'owner',
        ]);

        $this->post(route('register.store'), [
            'company_name' => 'Pet da Ana',
            'vertical' => 'pet_shop',
            'name' => 'Ana',
            'email' => 'ana@clientloop.test',
            'password' => 'password-password',
            'password_confirmation' => 'password-password',
        ])->assertRedirect();

        Notification::assertSentOnDemand(
            NewCompanyRegistered::class,
            function (NewCompanyRegistered $notification, array $channels, object $notifiable): bool {
                $recipients = $notifiable->routes['mail'];
                sort($recipients);

                return $channels === ['mail']
                    && $recipients === ['dono@clientloop.test', 'platform@clientloop.test']
                    && $notification->owner->email === 'ana@clientloop.test';
            },
        );
    }

    public function test_registration_sends_no_signup_email_without_a_recipient(): void
    {
        Notification::fake();
        Config::set('clientloop.require_email_verification', false);
        Config::set('clientloop.signup_notification_email', 'nao-e-email');

        $this->post(route('register.store'), [
            'company_name' => 'Pet da Ana',
            'vertical' => 'pet_shop',
            'name' => 'Ana',
            'email' => 'ana@clientloop.test',
            'password' => 'password-password',
            'password_confirmation' => 'password-password',
        ])->assertRedirect();

        Notification::assertNothingSent();
        $this->assertModelExists(User::where('email', 'ana@clientloop.test')->firstOrFail());
    }

    public function test_signup_mail_names_the_company_the_business_type_and_the_owner(): void
    {
        $company = Company::create([
            'name' => 'Pet da Ana',
            'slug' => 'pet-da-ana',
            'vertical' => CompanyVertical::PetShop,
        ]);
        $owner = User::create([
            'company_id' => $company->id,
            'name' => 'Ana',
            'email' => 'ana@clientloop.test',
            'password' => 'password-password',
            'role' => 'owner',
        ]);

        $mail = (new NewCompanyRegistered($owner))->toMail($owner);

        $this->assertSame('Novo cadastro no ClientLoop', $mail->subject);
        $rendered = (string) $mail->render();
        $this->assertStringContainsString('Pet da Ana', $rendered);
        $this->assertStringContainsString('Pet shop', $rendered);
        $this->assertStringContainsString('Ana', $rendered);
        $this->assertStringContainsString('ana@clientloop.test', $rendered);
    }
}
