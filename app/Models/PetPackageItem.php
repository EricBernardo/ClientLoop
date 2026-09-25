<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PetPackageItem extends TenantModel
{
    protected $fillable = ['company_id', 'pet_package_id', 'service_id', 'service_name', 'duration_minutes', 'position'];

    protected function casts(): array
    {
        return ['duration_minutes' => 'integer'];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(PetPackage::class, 'pet_package_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function redemption(): HasOne
    {
        return $this->hasOne(PackageRedemption::class);
    }
}
