<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class ServiceOrderItem extends TenantModel
{
    protected $fillable = ['company_id', 'service_order_id', 'service_id', 'description', 'quantity', 'unit_price'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function lineTotal(): string
    {
        $cents = (int) round(((float) $this->quantity) * ((float) $this->unit_price) * 100);

        return number_format($cents / 100, 2, '.', '');
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $item): void {
            if (! $item->company_id && $item->service_order_id) {
                $item->company_id = ServiceOrder::withoutGlobalScopes()->whereKey($item->service_order_id)->value('company_id');
            }
        });

        static::saving(function (self $item): void {
            if (blank($item->description)) {
                throw ValidationException::withMessages(['description' => 'Informe a descrição do item.']);
            }

            if ((float) $item->quantity <= 0) {
                throw ValidationException::withMessages(['quantity' => 'Informe uma quantidade maior que zero.']);
            }

            if ((float) $item->unit_price < 0) {
                throw ValidationException::withMessages(['unit_price' => 'O valor não pode ser negativo.']);
            }

            $order = ServiceOrder::withoutGlobalScopes()->whereKey($item->service_order_id)->first();

            if (! $order || (int) $order->company_id !== (int) $item->company_id) {
                throw ValidationException::withMessages(['description' => 'Item fora da ordem desta oficina.']);
            }

            if ($item->service_id) {
                $belongsToCompany = Service::withoutGlobalScopes()
                    ->whereKey($item->service_id)
                    ->where('company_id', $item->company_id)
                    ->exists();

                if (! $belongsToCompany) {
                    throw ValidationException::withMessages(['service_id' => 'Escolha um serviço desta oficina.']);
                }
            }
        });
    }
}
