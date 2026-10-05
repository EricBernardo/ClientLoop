<?php

namespace App\Models;

use App\Enums\ReceiptPaymentMethod;
use App\Enums\ReceiptStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceReceipt extends TenantModel
{
    protected $fillable = ['company_id', 'service_order_id', 'amount', 'payment_method', 'status', 'issued_on', 'paid_on'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => ReceiptPaymentMethod::class,
            'status' => ReceiptStatus::class,
            'issued_on' => 'date',
            'paid_on' => 'date',
        ];
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function cashEntry(): HasOne
    {
        return $this->hasOne(CashEntry::class);
    }
}
