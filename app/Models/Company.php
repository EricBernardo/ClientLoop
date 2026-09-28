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
        'public_booking_token',
    ];

    protected function casts(): array
    {
        return [
            'business_days' => 'array',
            'business_breaks' => 'array',
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

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'owner');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(CompanySubscription::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function currentUsage(): HasOne
    {
        return $this->hasOne(UsageRecord::class)->where('period', now()->format('Y-m'));
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
