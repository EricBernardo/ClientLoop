<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageOffer extends TenantModel
{
    protected $fillable = ['company_id', 'service_id', 'name', 'credits', 'suggested_price', 'active'];

    protected function casts(): array
    {
        return ['suggested_price' => 'decimal:2', 'active' => 'boolean'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(PetPackage::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackageOfferItem::class)->orderBy('position');
    }

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (self $offer): void {
            if ($offer->relationLoaded('items')) {
                $offer->credits = $offer->items->count();
                $offer->service_id = $offer->items->first()?->service_id;
            }
        });
    }
}
