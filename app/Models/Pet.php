<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pet extends TenantModel
{
    protected $fillable = ['company_id', 'customer_id', 'name', 'species', 'breed', 'size', 'notes'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(PetPackage::class);
    }
}
