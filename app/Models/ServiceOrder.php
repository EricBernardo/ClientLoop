<?php

namespace App\Models;

use App\Enums\ServiceOrderStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class ServiceOrder extends TenantModel
{
    protected $fillable = ['company_id', 'customer_id', 'vehicle_id', 'opened_on', 'status', 'notes'];

    protected $attributes = [
        'status' => 'aberta',
    ];

    protected function casts(): array
    {
        return [
            'opened_on' => 'date',
            'status' => ServiceOrderStatus::class,
            'status_changed_at' => 'datetime',
            'ready_alerted_at' => 'datetime',
            'unpaid_delivery_alerted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(ServiceReceipt::class);
    }

    public function totalAmount(): string
    {
        $this->loadMissing('items');
        $cents = 0;

        foreach ($this->items as $item) {
            $cents += (int) round(((float) $item->quantity) * ((float) $item->unit_price) * 100);
        }

        return number_format($cents / 100, 2, '.', '');
    }

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (self $order): void {
            $customer = Customer::withoutGlobalScopes()
                ->whereKey($order->customer_id)
                ->where('company_id', $order->company_id)
                ->first();
            $vehicle = Vehicle::withoutGlobalScopes()
                ->whereKey($order->vehicle_id)
                ->where('company_id', $order->company_id)
                ->first();

            if (! $customer || ! $vehicle || (int) $vehicle->customer_id !== (int) $customer->id) {
                throw ValidationException::withMessages(['vehicle_id' => 'O veículo precisa ser do cliente escolhido.']);
            }

            if ($order->exists && $order->status === ServiceOrderStatus::Cancelled && $order->receipt()->exists()) {
                throw ValidationException::withMessages(['status' => 'Esta ordem já tem recibo. Não dá para cancelar.']);
            }

            if (! $order->exists || $order->isDirty('status')) {
                $order->status_changed_at = now();

                if ($order->status !== ServiceOrderStatus::Ready) {
                    $order->ready_alerted_at = null;
                }

                if ($order->status !== ServiceOrderStatus::Delivered) {
                    $order->unpaid_delivery_alerted_at = null;
                }
            }
        });
    }
}
