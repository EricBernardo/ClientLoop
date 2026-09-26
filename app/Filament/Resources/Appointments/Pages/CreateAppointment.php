<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Concerns\HandlesFriendlyValidation;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateAppointment extends CreateRecord
{
    use HandlesFriendlyValidation;

    protected static string $resource = AppointmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['confirmation_token'] ??= Str::random(40);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $weeks = (int) ($this->data['recurrence_weeks'] ?? 1);
        $count = (int) ($this->data['recurrence_count'] ?? 1);

        if ($count > 1) {
            $prototype = new Appointment($data);
            $prototype->company_id = $data['company_id'] ?? auth()->user()?->company_id;
            $created = app(AppointmentService::class)->createRecurring($prototype, max(1, $weeks), $count);

            return $created[0];
        }

        return parent::handleRecordCreation($data);
    }

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (ValidationException $exception) {
            $this->showFriendlyValidation($exception);
        }
    }
}
