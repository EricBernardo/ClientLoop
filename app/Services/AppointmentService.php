<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function complete(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $appointment = Appointment::withoutGlobalScopes()->lockForUpdate()->findOrFail($appointment->id);
            if ($appointment->status === 'completed') {
                return $appointment;
            }
            if (! in_array($appointment->status, ['scheduled', 'confirmed'], true)) {
                throw ValidationException::withMessages(['appointment' => 'Este agendamento não pode ser concluído nesta situação.']);
            }

            $appointment->loadMissing('customer', 'service', 'package');
            $packageService = app(PackageService::class);
            $packageService->consume($appointment);
            $appointment->update(['status' => 'completed']);

            $nextPackageItem = $appointment->package ? $packageService->nextItem($appointment->package) : null;
            $returnAt = $nextPackageItem
                ? null
                : ($appointment->service?->return_interval_months ? now()->addMonths($appointment->service->return_interval_months) : null);

            $appointment->customer?->update([
                'last_activity_at' => now(),
                'next_return_at' => $returnAt,
            ]);

            return $appointment->fresh();
        });
    }
}
