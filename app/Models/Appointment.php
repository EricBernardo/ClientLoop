<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends TenantModel
{
    protected $fillable = ['company_id', 'customer_id', 'service_id', 'external_id', 'scheduled_at', 'status', 'potential_value', 'realized_value', 'origin', 'next_return_at'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'next_return_at' => 'datetime', 'potential_value' => 'decimal:2', 'realized_value' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ContactTask::class);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::updating(function (self $appointment): void {
            if (! $appointment->isDirty('status')) {
                return;
            }
            $allowed = [
                'scheduled' => ['confirmed', 'reschedule_requested', 'cancelled', 'no_show'],
                'confirmed' => ['reschedule_requested', 'cancelled', 'no_show', 'completed'],
                'reschedule_requested' => ['scheduled', 'cancelled'],
                'cancelled' => [], 'no_show' => [], 'completed' => [],
            ];
            if (! in_array($appointment->status, $allowed[$appointment->getOriginal('status')] ?? [], true)) {
                throw new \DomainException('Transição de agendamento inválida.');
            }
        });
    }
}
