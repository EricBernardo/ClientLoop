<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends TenantModel
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'pet_id',
        'service_id',
        'preferred_date',
        'preferred_time',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return ['preferred_date' => 'date'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
