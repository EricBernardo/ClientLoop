<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NewCompanyRegistered;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SignupNotifier
{
    public function notify(User $owner): void
    {
        $recipients = $this->recipients();

        if ($recipients === []) {
            return;
        }

        try {
            Notification::route('mail', $recipients)->notify(new NewCompanyRegistered($owner));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @return list<string>
     */
    private function recipients(): array
    {
        $configured = str((string) config('clientloop.signup_notification_email'))
            ->explode(',')
            ->map(fn (string $email): string => trim($email))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false);

        $superAdmins = User::query()
            ->where('is_super_admin', true)
            ->pluck('email')
            ->filter(fn (mixed $email): bool => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false);

        return $configured
            ->merge($superAdmins)
            ->map(fn (string $email): string => strtolower($email))
            ->unique()
            ->values()
            ->all();
    }
}
