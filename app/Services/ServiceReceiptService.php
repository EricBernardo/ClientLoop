<?php

namespace App\Services;

use App\Enums\CashEntryDirection;
use App\Enums\ReceiptPaymentMethod;
use App\Enums\ReceiptStatus;
use App\Enums\ServiceOrderStatus;
use App\Models\CashEntry;
use App\Models\ServiceOrder;
use App\Models\ServiceReceipt;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceReceiptService
{
    public function issue(ServiceOrder $order, string $amount, ReceiptPaymentMethod $method, ReceiptStatus $status, CarbonInterface $issuedOn): ServiceReceipt
    {
        if ($order->status === ServiceOrderStatus::Cancelled) {
            throw ValidationException::withMessages(['status' => 'Não é possível lançar recibo de uma ordem cancelada.']);
        }

        if ($order->receipt()->exists()) {
            throw ValidationException::withMessages(['receipt' => 'Esta ordem já tem um recibo.']);
        }

        return DB::transaction(function () use ($order, $amount, $method, $status, $issuedOn): ServiceReceipt {
            $receipt = $order->receipt()->create([
                'company_id' => $order->company_id,
                'amount' => $amount,
                'payment_method' => $method,
                'status' => $status,
                'issued_on' => $issuedOn->toDateString(),
                'paid_on' => $status === ReceiptStatus::Paid ? $issuedOn->toDateString() : null,
            ]);

            if ($receipt->status === ReceiptStatus::Paid) {
                $this->syncIncome($receipt);
            }

            return $receipt;
        });
    }

    public function markPaid(ServiceReceipt $receipt, CarbonInterface $paidOn): ServiceReceipt
    {
        return DB::transaction(function () use ($receipt, $paidOn): ServiceReceipt {
            $receipt->update([
                'status' => ReceiptStatus::Paid,
                'paid_on' => $paidOn->toDateString(),
            ]);
            $this->syncIncome($receipt->fresh());

            return $receipt->fresh();
        });
    }

    public function markPending(ServiceReceipt $receipt): ServiceReceipt
    {
        return DB::transaction(function () use ($receipt): ServiceReceipt {
            $receipt->update([
                'status' => ReceiptStatus::Pending,
                'paid_on' => null,
            ]);
            CashEntry::withoutGlobalScopes()->where('service_receipt_id', $receipt->id)->delete();

            return $receipt->fresh();
        });
    }

    private function syncIncome(ServiceReceipt $receipt): void
    {
        CashEntry::withoutGlobalScopes()->updateOrCreate(
            ['service_receipt_id' => $receipt->id],
            [
                'company_id' => $receipt->company_id,
                'direction' => CashEntryDirection::Income,
                'category' => null,
                'amount' => $receipt->amount,
                'occurred_on' => $receipt->paid_on?->toDateString() ?? $receipt->issued_on->toDateString(),
                'notes' => 'Recibo da ordem '.$receipt->service_order_id,
            ],
        );
    }
}
