<?php

namespace App\Models;

use App\Enums\CashEntryDirection;
use App\Enums\CashExpenseCategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashEntry extends TenantModel
{
    protected $fillable = ['company_id', 'service_receipt_id', 'direction', 'category', 'amount', 'occurred_on', 'notes'];

    protected function casts(): array
    {
        return [
            'direction' => CashEntryDirection::class,
            'category' => CashExpenseCategory::class,
            'amount' => 'decimal:2',
            'occurred_on' => 'date',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(ServiceReceipt::class, 'service_receipt_id');
    }

    public function description(): string
    {
        if ($this->direction === CashEntryDirection::Income) {
            $customer = $this->receipt?->serviceOrder?->customer?->name;

            return $customer ? 'Recibo · '.$customer : 'Recibo';
        }

        return $this->category?->label() ?? 'Saída';
    }
}
