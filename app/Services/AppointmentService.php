<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\ContactTask;
use App\Models\PackageRedemption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

            if (! $appointment->confirmation_token) {
                $appointment->confirmation_token = Str::random(40);
            }

            $appointment->loadMissing('customer', 'service', 'package', 'pet');
            $packageService = app(PackageService::class);
            $packageService->consume($appointment);
            $appointment->update(['status' => 'completed', 'confirmation_token' => $appointment->confirmation_token]);

            $package = $appointment->package?->fresh(['items', 'redemptions']);
            $nextPackageItem = $package ? $packageService->nextItem($package) : null;
            $returnAt = $nextPackageItem
                ? null
                : ($appointment->service?->return_interval_months ? now()->addMonths($appointment->service->return_interval_months) : null);

            $appointment->customer?->update([
                'last_activity_at' => now(),
                'next_return_at' => $returnAt,
            ]);

            if ($package && $packageService->nextItem($package) === null && $package->remaining_credits === 0) {
                $this->queuePackageRenewal($appointment);
            }

            return $appointment->fresh();
        });
    }

    public function undoComplete(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $appointment = Appointment::withoutGlobalScopes()->lockForUpdate()->findOrFail($appointment->id);

            if ($appointment->status !== 'completed') {
                throw ValidationException::withMessages(['appointment' => 'Só é possível desfazer um atendimento já concluído.']);
            }

            PackageRedemption::withoutGlobalScopes()->where('appointment_id', $appointment->id)->delete();
            $appointment->update(['status' => 'confirmed']);
            $this->log($appointment->company_id, 'appointment.undo_complete', $appointment);

            return $appointment->fresh();
        });
    }

    public function markNoShow(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $appointment = Appointment::withoutGlobalScopes()->lockForUpdate()->findOrFail($appointment->id);

            if (! in_array($appointment->status, ['scheduled', 'confirmed'], true)) {
                throw ValidationException::withMessages(['appointment' => 'Este agendamento não pode ser marcado como falta nesta situação.']);
            }

            $this->cancelPendingTasks($appointment, 'no_show');
            $appointment->update(['status' => 'no_show']);
            $this->log($appointment->company_id, 'appointment.no_show', $appointment);
            $this->queueFollowUp($appointment, 'no_show');

            return $appointment->fresh();
        });
    }

    public function cancel(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $appointment = Appointment::withoutGlobalScopes()->lockForUpdate()->findOrFail($appointment->id);

            if (! in_array($appointment->status, ['scheduled', 'confirmed', 'reschedule_requested'], true)) {
                throw ValidationException::withMessages(['appointment' => 'Este agendamento não pode ser cancelado nesta situação.']);
            }

            $this->cancelPendingTasks($appointment, 'cancelled');
            $appointment->update(['status' => 'cancelled']);
            $this->log($appointment->company_id, 'appointment.cancelled', $appointment);
            $this->queueFollowUp($appointment, 'cancelled');

            return $appointment->fresh();
        });
    }

    /**
     * @return list<Appointment>
     */
    public function createRecurring(Appointment $prototype, int $weeks, int $count): array
    {
        if ($count < 1 || $count > 12) {
            throw ValidationException::withMessages(['recurrence' => 'Gere entre 1 e 12 visitas recorrentes.']);
        }

        $group = (string) Str::uuid();
        $created = [];
        $base = $prototype->scheduled_at->copy();

        for ($i = 0; $i < $count; $i++) {
            $appointment = Appointment::withoutGlobalScopes()->create([
                'company_id' => $prototype->company_id,
                'customer_id' => $prototype->customer_id,
                'pet_id' => $prototype->pet_id,
                'service_id' => $prototype->service_id,
                'pet_package_id' => $i === 0 ? $prototype->pet_package_id : null,
                'groomer_id' => $prototype->groomer_id,
                'scheduled_at' => $base->copy()->addWeeks($i * max(1, $weeks)),
                'duration_minutes' => $prototype->duration_minutes,
                'status' => 'scheduled',
                'recurrence_group' => $group,
                'confirmation_token' => Str::random(40),
            ]);
            if ($i === 0) {
                $this->queueFirstVisitConfirmation($appointment);
            }

            $created[] = $appointment;
        }

        return $created;
    }

    public function queueFirstVisitConfirmation(Appointment $appointment): void
    {
        $company = Company::query()->find($appointment->company_id);

        if (! $company || $company->setup_wizard_completed_at !== null) {
            return;
        }

        $appointment->loadMissing('customer', 'service', 'pet');

        if (! $appointment->customer) {
            return;
        }

        app(ContactTaskService::class)->create(
            $company,
            $appointment->customer,
            'confirmation',
            now(),
            [
                'appointment' => $appointment,
                'service' => $appointment->service,
                'pet' => $appointment->pet,
                'cycle_key' => 'appointment:'.$appointment->id,
            ],
        );
    }

    private function queueFollowUp(Appointment $appointment, string $reason): void
    {
        $appointment->loadMissing('customer', 'service', 'pet', 'company');
        $company = $appointment->company ?? Company::query()->find($appointment->company_id);
        $customer = $appointment->customer;

        if (! $company || ! $customer) {
            return;
        }

        app(ContactTaskService::class)->create(
            $company,
            $customer,
            'recall',
            now(),
            [
                'appointment' => $appointment,
                'service' => $appointment->service,
                'pet' => $appointment->pet,
                'cycle_key' => 'followup:'.$reason.':'.$appointment->id,
            ]
        );
    }

    private function queuePackageRenewal(Appointment $appointment): void
    {
        $appointment->loadMissing('customer', 'service', 'pet', 'company', 'package');
        $company = $appointment->company ?? Company::query()->find($appointment->company_id);
        $customer = $appointment->customer;

        if (! $company || ! $customer) {
            return;
        }

        app(ContactTaskService::class)->create(
            $company,
            $customer,
            'recall',
            now(),
            [
                'service' => $appointment->service,
                'pet' => $appointment->pet,
                'cycle_key' => 'package_renewal:'.$appointment->pet_package_id,
            ]
        );
    }

    private function cancelPendingTasks(Appointment $appointment, string $outcome): void
    {
        ContactTask::withoutGlobalScopes()
            ->where('appointment_id', $appointment->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
                'outcome' => $outcome,
                'completed_at' => now(),
            ]);
    }

    private function log(int $companyId, string $event, Appointment $appointment): void
    {
        ActivityLog::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'user_id' => auth()->id(),
            'event' => $event,
            'subject_type' => $appointment::class,
            'subject_id' => $appointment->id,
            'properties' => ['status' => $appointment->status],
        ]);
    }
}
