<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Concerns\HandlesFriendlyValidation;
use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAppointment extends CreateRecord
{
    use HandlesFriendlyValidation;

    protected static string $resource = AppointmentResource::class;

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (ValidationException $exception) {
            $this->showFriendlyValidation($exception);
        }
    }
}
