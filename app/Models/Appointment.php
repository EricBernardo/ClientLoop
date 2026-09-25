<?php

namespace App\Models;

use App\Services\PackageService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class Appointment extends TenantModel
{
    protected $fillable = ['company_id', 'customer_id', 'pet_id', 'service_id', 'pet_package_id', 'scheduled_at', 'duration_minutes', 'ends_at', 'status'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'ends_at' => 'datetime', 'duration_minutes' => 'integer'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(PetPackage::class, 'pet_package_id');
    }

    public function packageRedemption(): HasOne
    {
        return $this->hasOne(PackageRedemption::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ContactTask::class);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (self $appointment): void {
            if (! $appointment->scheduled_at) {
                return;
            }

            if (! $appointment->duration_minutes) {
                $appointment->duration_minutes = $appointment->service?->duration_minutes ?? 60;
            }
            $appointment->ends_at = $appointment->scheduled_at->copy()->addMinutes($appointment->duration_minutes);
            if ($appointment->pet_id && ! Pet::withoutGlobalScopes()->whereKey($appointment->pet_id)->where('company_id', $appointment->company_id ?: auth()->user()?->company_id)->where('customer_id', $appointment->customer_id)->exists()) {
                throw ValidationException::withMessages(['pet_id' => 'Escolha um pet que pertença ao responsável selecionado.']);
            }
            if ($appointment->pet_package_id && (! $appointment->exists || $appointment->isDirty(['pet_package_id', 'pet_id', 'service_id']))) {
                app(PackageService::class)->assertCanUse($appointment->pet_package_id, $appointment->pet_id, $appointment->service_id);
            }
            if (! $appointment->exists || $appointment->isDirty(['scheduled_at', 'duration_minutes', 'pet_id'])) {
                $appointment->ensureBusinessHours();
            }
            $appointment->ensureAvailability();
        });

        static::updating(function (self $appointment): void {
            if (! $appointment->isDirty('status')) {
                return;
            }
            $allowed = [
                'scheduled' => ['confirmed', 'reschedule_requested', 'cancelled', 'no_show', 'completed'],
                'confirmed' => ['reschedule_requested', 'cancelled', 'no_show', 'completed'],
                'reschedule_requested' => ['scheduled', 'cancelled'],
                'cancelled' => [], 'no_show' => [], 'completed' => [],
            ];
            if (! in_array($appointment->status, $allowed[$appointment->getOriginal('status')] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Esta mudança de situação não é permitida para o agendamento.']);
            }
        });
    }

    public function ensureAvailability(): void
    {
        if (! $this->scheduled_at || in_array($this->status, ['cancelled', 'no_show', 'completed'], true)) {
            return;
        }

        $companyId = $this->company_id ?: auth()->user()?->company_id;
        if (! $companyId) {
            return;
        }

        $overlaps = static::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->whereIn('status', ['scheduled', 'confirmed', 'reschedule_requested'])
            ->where('scheduled_at', '<', $this->ends_at)
            ->where('ends_at', '>', $this->scheduled_at)
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages(['scheduled_at' => 'Este horário já está ocupado. Escolha outro horário disponível.']);
        }
    }

    public function ensureBusinessHours(): void
    {
        if (! $this->scheduled_at || ! $this->pet_id) {
            return;
        }

        $companyId = $this->company_id ?: auth()->user()?->company_id;
        $company = $companyId ? Company::find($companyId) : null;
        if (! $company) {
            return;
        }

        $startsAtHour = $company->business_starts_at_hour ?? 9;
        $endsAtHour = $company->business_ends_at_hour ?? 17;
        $slotMinutes = $company->appointment_slot_minutes ?? 60;
        $businessDays = $company->business_days ?: [1, 2, 3, 4, 5, 6];
        $scheduledAt = Carbon::parse($this->scheduled_at)->setTimezone($company->timezone);
        $endsAt = Carbon::parse($this->ends_at)->setTimezone($company->timezone);
        $opensAt = $scheduledAt->copy()->setTime($startsAtHour, 0);
        $closesAt = $scheduledAt->copy()->setTime($endsAtHour, 0);

        if (! in_array($scheduledAt->dayOfWeekIso, $businessDays, true)) {
            throw ValidationException::withMessages(['scheduled_at' => 'Não há atendimento neste dia. Escolha um dia de segunda a sábado.']);
        }
        if ($scheduledAt->minute % $slotMinutes !== 0 || $scheduledAt->second !== 0) {
            throw ValidationException::withMessages(['scheduled_at' => 'Escolha um horário cheio, como 09:00, 10:00 ou 11:00.']);
        }
        if ($scheduledAt->lt($opensAt) || $endsAt->gt($closesAt)) {
            throw ValidationException::withMessages(['scheduled_at' => 'Este atendimento termina fora do expediente. Escolha um horário que caiba na agenda.']);
        }
    }
}
