<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $fillable = ['name', 'slug', 'timezone', 'status', 'confirmation_hours', 'reactivation_months', 'business_days', 'business_starts_at_hour', 'business_ends_at_hour', 'appointment_slot_minutes'];

    protected function casts(): array
    {
        return ['business_days' => 'array'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(CompanySubscription::class);
    }
}
