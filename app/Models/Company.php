<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'timezone',
        'status',
        'confirmation_hours',
        'reactivation_months',
        'business_days',
        'business_starts_at_hour',
        'business_ends_at_hour',
        'appointment_slot_minutes',
        'business_breaks',
        'onboarding_completed_at',
        'setup_wizard_completed_at',
        'hours_configured_at',
        'guide_viewed_at',
        'public_booking_token',
    ];

    protected function casts(): array
    {
        return [
            'business_days' => 'array',
            'business_breaks' => 'array',
            'onboarding_completed_at' => 'datetime',
            'setup_wizard_completed_at' => 'datetime',
            'hours_configured_at' => 'datetime',
            'guide_viewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $company): void {
            if (blank($company->public_booking_token)) {
                $company->public_booking_token = Str::random(40);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(CompanySubscription::class);
    }

    public function groomers(): HasMany
    {
        return $this->hasMany(Groomer::class);
    }

    public function publicBookingUrl(?int $customerId = null): string
    {
        if (blank($this->public_booking_token)) {
            $this->forceFill(['public_booking_token' => Str::random(40)])->saveQuietly();
        }

        $url = route('booking.show', $this->public_booking_token);

        return $customerId ? $url.'?customer_id='.$customerId : $url;
    }
}
