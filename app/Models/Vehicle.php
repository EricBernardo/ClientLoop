<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Vehicle extends TenantModel
{
    protected $fillable = ['company_id', 'customer_id', 'plate', 'brand', 'model', 'year', 'mileage', 'notes'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'mileage' => 'integer',
        ];
    }

    protected function plate(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): ?string {
                if ($value === null) {
                    return null;
                }

                return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $value));
            },
        );
    }

    public function label(): string
    {
        $description = trim(implode(' ', array_filter([$this->brand, $this->model])));

        return $description === '' ? (string) $this->plate : $this->plate.' · '.$description;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (self $vehicle): void {
            if (blank($vehicle->plate)) {
                throw ValidationException::withMessages(['plate' => 'Informe a placa.']);
            }

            $customer = Customer::withoutGlobalScopes()
                ->whereKey($vehicle->customer_id)
                ->where('company_id', $vehicle->company_id)
                ->first();

            if (! $customer) {
                throw ValidationException::withMessages(['customer_id' => 'Escolha um cliente desta oficina.']);
            }

            $plateTaken = static::withoutGlobalScopes()
                ->where('company_id', $vehicle->company_id)
                ->where('plate', $vehicle->plate)
                ->when($vehicle->exists, fn ($query) => $query->whereKeyNot($vehicle->id))
                ->exists();

            if ($plateTaken) {
                throw ValidationException::withMessages(['plate' => 'Esta placa já está cadastrada.']);
            }
        });
    }
}
