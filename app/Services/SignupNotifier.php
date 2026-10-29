<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NewCompanyRegistered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SignupNotifier
{
    public function notify(User $owner): void
    {
        $resolved = $this->resolveRecipients();
        $recipients = $resolved['recipients'];

        Log::info('signup_notification.resolve', [
            'owner_id' => $owner->id,
            'owner_email' => $owner->email,
            'company_id' => $owner->company_id,
            'raw_config' => $resolved['raw_config'],
            'configured' => $resolved['configured'],
            'invalid_configured' => $resolved['invalid_configured'],
            'super_admins' => $resolved['super_admins'],
            'recipients' => $recipients,
            'mail_mailer' => config('mail.default'),
            'brevo_key_configured' => filled(config('mail.mailers.brevo.key')),
            'brevo_key_length' => strlen((string) config('mail.mailers.brevo.key')),
        ]);

        if ($recipients === []) {
            Log::warning('signup_notification.skipped_no_recipients', [
                'owner_id' => $owner->id,
                'owner_email' => $owner->email,
                'raw_config' => $resolved['raw_config'],
                'invalid_configured' => $resolved['invalid_configured'],
                'hint' => 'Defina CLIENTLOOP_SIGNUP_NOTIFICATION_EMAIL com e-mails válidos ou crie um usuário is_super_admin=true.',
            ]);

            return;
        }

        try {
            Notification::route('mail', $recipients)->notify(new NewCompanyRegistered($owner));

            Log::info('signup_notification.sent', [
                'owner_id' => $owner->id,
                'owner_email' => $owner->email,
                'recipients' => $recipients,
                'mail_mailer' => config('mail.default'),
            ]);
        } catch (Throwable $exception) {
            Log::error('signup_notification.failed', [
                'owner_id' => $owner->id,
                'owner_email' => $owner->email,
                'recipients' => $recipients,
                'mail_mailer' => config('mail.default'),
                'brevo_key_configured' => filled(config('mail.mailers.brevo.key')),
                'brevo_key_length' => strlen((string) config('mail.mailers.brevo.key')),
                'exception' => $exception->getMessage(),
            ]);

            report($exception);
        }
    }

    /**
     * @return array{
     *     raw_config: string|null,
     *     configured: list<string>,
     *     invalid_configured: list<string>,
     *     super_admins: list<string>,
     *     recipients: list<string>
     * }
     */
    private function resolveRecipients(): array
    {
        $rawConfig = config('clientloop.signup_notification_email');
        $rawConfigString = is_string($rawConfig) ? $rawConfig : (is_null($rawConfig) ? null : (string) $rawConfig);

        $configuredParts = str((string) $rawConfigString)
            ->explode(',')
            ->map(fn (string $email): string => trim($email))
            ->filter(fn (string $email): bool => $email !== '')
            ->values();

        $configured = $configuredParts
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->values();

        $invalidConfigured = $configuredParts
            ->reject(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->values()
            ->all();

        $superAdmins = User::query()
            ->where('is_super_admin', true)
            ->pluck('email')
            ->filter(fn (mixed $email): bool => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->map(fn (string $email): string => strtolower($email))
            ->values()
            ->all();

        $recipients = $configured
            ->map(fn (string $email): string => strtolower($email))
            ->merge($superAdmins)
            ->unique()
            ->values()
            ->all();

        return [
            'raw_config' => $rawConfigString,
            'configured' => $configured->map(fn (string $email): string => strtolower($email))->values()->all(),
            'invalid_configured' => $invalidConfigured,
            'super_admins' => $superAdmins,
            'recipients' => $recipients,
        ];
    }
}
