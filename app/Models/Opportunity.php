<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends TenantModel
{
    protected $fillable = ['company_id', 'customer_id', 'service_id', 'appointment_id', 'title', 'stage', 'urgency', 'potential_value', 'realized_value', 'next_follow_up_at', 'loss_reason', 'notes'];

    protected function casts(): array
    {
        return ['next_follow_up_at' => 'datetime', 'potential_value' => 'decimal:2', 'realized_value' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
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

        static::creating(function (self $opportunity): void {
            $opportunity->next_follow_up_at ??= now()->addDay();
        });

        static::updating(function (self $opportunity): void {
            if (! $opportunity->isDirty('stage')) {
                return;
            }
            $allowed = [
                'new' => ['qualification', 'proposal', 'scheduling', 'won', 'lost'],
                'qualification' => ['proposal', 'scheduling', 'won', 'lost'],
                'proposal' => ['scheduling', 'won', 'lost'],
                'scheduling' => ['won', 'lost'],
                'won' => [], 'lost' => [],
            ];
            if (! in_array($opportunity->stage, $allowed[$opportunity->getOriginal('stage')] ?? [], true)) {
                throw new \DomainException('Transição de oportunidade inválida.');
            }
        });
    }
}
