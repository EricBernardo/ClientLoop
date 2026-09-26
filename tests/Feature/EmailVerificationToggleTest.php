<?php

namespace Tests\Feature;

use App\Filament\Pages\BusinessSettings;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_email_verification_when_enabled(): void
    {
        Notification::fake();
        Config::set('clientloop.require_email_verification', true);

        $email = 'nova-empresa@clientloop.test';

        $this->post(route('register.store'), [
            'company_name' => 'Pet Shop Verificação',
            'name' => 'Marina',
            'email' => $email,
            'password' => 'password-password',
            'password_confirmation' => 'password-password',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', $email)->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            $this->assertSame('Confirme seu e-mail', $mail->subject);
            $this->assertContains('Clique no botão abaixo para confirmar seu endereço de e-mail.', $mail->introLines);
            $this->assertSame('Confirmar e-mail', $mail->actionText);
            $this->assertContains('Se você não criou uma conta, nenhuma ação adicional é necessária.', $mail->outroLines);

            return true;
        });
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('company')));
    }

    public function test_registration_skips_email_verification_when_disabled(): void
    {
        Notification::fake();
        Config::set('clientloop.require_email_verification', false);

        $email = 'sem-verificacao@clientloop.test';

        $this->post(route('register.store'), [
            'company_name' => 'Pet Shop Sem Verificação',
            'name' => 'Carla',
            'email' => $email,
            'password' => 'password-password',
            'password_confirmation' => 'password-password',
        ])->assertRedirect(BusinessSettings::getUrl(panel: 'company'));

        $user = User::where('email', $email)->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        Notification::assertNothingSent();
        $this->assertTrue($user->fresh()->canAccessPanel(Filament::getPanel('company')));
    }

    public function test_unverified_user_can_access_panel_when_verification_is_disabled(): void
    {
        Config::set('clientloop.require_email_verification', false);

        $company = Company::create([
            'name' => 'Empresa sem verificação',
            'slug' => 'empresa-sem-verificacao-'.fake()->unique()->slug(),
            'status' => 'trial',
        ]);
        $user = User::factory()->unverified()->create([
            'company_id' => $company->id,
            'is_super_admin' => false,
        ]);

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('company')));
    }

    public function test_unverified_user_can_download_import_template_when_verification_is_disabled(): void
    {
        Config::set('clientloop.require_email_verification', false);

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('imports.template', ['type' => 'customers']))
            ->assertOk()
            ->assertDownload('modelo-importacao-responsaveis.csv');
    }
}
