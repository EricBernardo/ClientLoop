<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageRedemption extends TenantModel
{
    protected $fillable = ['company_id', 'pet_package_id', 'pet_package_item_id', 'appointment_id', 'redeemed_at'];

    protected function casts(): array
    {
        return ['redeemed_at' => 'datetime'];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(PetPackage::class, 'pet_package_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function packageItem(): BelongsTo
    {
        return $this->belongsTo(PetPackageItem::class, 'pet_package_item_id');
    }
}
