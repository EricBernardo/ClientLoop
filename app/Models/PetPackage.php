<?php

namespace App\Models;

use App\Services\PackageService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class PetPackage extends TenantModel
{
    protected $fillable = ['company_id', 'pet_id', 'package_offer_id', 'name', 'total_credits', 'price', 'payment_status', 'purchased_at', 'valid_until'];

    protected $appends = ['remaining_credits'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'purchased_at' => 'date', 'valid_until' => 'date'];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(PackageOffer::class, 'package_offer_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PackageRedemption::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PetPackageItem::class)->orderBy('position');
    }

    public function getRemainingCreditsAttribute(): int
    {
        $total = $this->relationLoaded('items') ? $this->items->count() : $this->items()->count();
        $total = $total ?: $this->total_credits;
        $used = $this->relationLoaded('redemptions') ? $this->redemptions->count() : $this->redemptions()->count();

        return max(0, $total - $used);
    }

    public function isUsableFor(int $petId, ?int $serviceId): bool
    {
        return $this->payment_status === 'paid'
            && $this->pet_id === $petId
            && ($this->valid_until === null || $this->valid_until->endOfDay()->gte(now()))
            && $this->remaining_credits > 0
            && app(PackageService::class)->nextItem($this)?->service_id === $serviceId;
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $package): void {
            if (! $package->package_offer_id) {
                return;
            }

            $offer = PackageOffer::withoutGlobalScopes()->find($package->package_offer_id);
            if (! $offer || $offer->company_id !== $package->company_id) {
                throw ValidationException::withMessages(['package_offer_id' => 'Escolha um modelo de pacote disponível para a sua empresa.']);
            }
            $package->name ??= $offer->name;
            $package->total_credits ??= $offer->credits;
            $package->price ??= $offer->suggested_price;
        });

        static::created(function (self $package): void {
            if (! $package->package_offer_id || $package->items()->exists()) {
                // still clear return when selling a paid package without copying items again
            } else {
                $offerItems = PackageOfferItem::withoutGlobalScopes()
                    ->where('package_offer_id', $package->package_offer_id)
                    ->with('service')
                    ->orderBy('position')
                    ->get();

                foreach ($offerItems as $item) {
                    $package->items()->create([
                        'company_id' => $package->company_id,
                        'service_id' => $item->service_id,
                        'service_name' => $item->service->name,
                        'duration_minutes' => $item->service->duration_minutes,
                        'position' => $item->position,
                    ]);
                }
            }

            if ($package->payment_status === 'paid') {
                $package->loadMissing('pet.customer');
                $package->pet?->customer?->update(['next_return_at' => null]);
            }
        });
    }
}
