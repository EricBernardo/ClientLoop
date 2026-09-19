<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends TenantModel
{
    protected $fillable = ['company_id', 'name', 'phone', 'email', 'tags', 'notes', 'last_activity_at', 'next_return_at', 'opted_out_at', 'opt_out_note'];

    protected function casts(): array
    {
        return ['tags' => 'array', 'last_activity_at' => 'datetime', 'next_return_at' => 'datetime', 'opted_out_at' => 'datetime'];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ContactTask::class);
    }

    public function getCanContactAttribute(): bool
    {
        return $this->opted_out_at === null;
    }

    protected static function booted(): void
    {
        parent::booted();

        static::updated(function (self $customer): void {
            if ($customer->wasChanged('opted_out_at') && $customer->opted_out_at) {
                ContactTask::withoutGlobalScopes()->where('customer_id', $customer->id)->where('status', 'pending')->update(['status' => 'cancelled', 'outcome' => 'opt_out', 'completed_at' => now()]);
            }
        });
    }
}
