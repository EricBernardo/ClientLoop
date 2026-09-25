<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

trait HandlesFriendlyValidation
{
    protected function showFriendlyValidation(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $this->addError(str_starts_with($field, 'data.') ? $field : "data.{$field}", $messages[0]);
        }

        Notification::make()
            ->danger()
            ->title('Não foi possível salvar o agendamento')
            ->body((string) (collect($exception->errors())->flatten()->first() ?? 'Confira os campos destacados e tente novamente.'))
            ->send();
    }
}
