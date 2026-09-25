<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class PackageOfferItem extends TenantModel
{
    protected $fillable = ['company_id', 'package_offer_id', 'service_id', 'position'];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(PackageOffer::class, 'package_offer_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::saved(fn (self $item) => $item->refreshOfferSummary());
        static::deleted(fn (self $item) => $item->refreshOfferSummary());
    }

    private function refreshOfferSummary(): void
    {
        $first = static::withoutGlobalScopes()
            ->where('package_offer_id', $this->package_offer_id)
            ->orderBy('position')
            ->first(['service_id']);
        $count = static::withoutGlobalScopes()->where('package_offer_id', $this->package_offer_id)->count();

        DB::table('package_offers')->where('id', $this->package_offer_id)->update([
            'service_id' => $first?->service_id,
            'credits' => $count,
            'updated_at' => now(),
        ]);
    }
}
