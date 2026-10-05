<?php

namespace App\Console\Commands;

use App\Enums\ReceiptStatus;
use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use App\Services\StaffNotifier;
use Illuminate\Console\Command;

class AlertWorkshopOrders extends Command
{
    protected $signature = 'clientloop:alert-workshop';

    protected $description = 'Avisa a oficina sobre veículo pronto parado e entrega com recibo pendente.';

    public function handle(StaffNotifier $notifier): int
    {
        $cutoff = now()->startOfDay();

        ServiceOrder::withoutGlobalScopes()
            ->with([
                'customer' => fn ($query) => $query->withoutGlobalScopes(),
                'vehicle' => fn ($query) => $query->withoutGlobalScopes(),
                'company',
            ])
            ->where('status', ServiceOrderStatus::Ready)
            ->whereNull('ready_alerted_at')
            ->whereNotNull('status_changed_at')
            ->where('status_changed_at', '<', $cutoff)
            ->lazyById()
            ->each(function (ServiceOrder $order) use ($notifier): void {
                $claimed = ServiceOrder::withoutGlobalScopes()
                    ->whereKey($order->id)
                    ->whereNull('ready_alerted_at')
                    ->update(['ready_alerted_at' => now()]);

                if ($claimed !== 1) {
                    return;
                }

                $notifier->orderReadyWaiting($order);
                $this->info("Ordem {$order->id} avisada: veículo pronto parado.");
            });

        ServiceOrder::withoutGlobalScopes()
            ->with([
                'customer' => fn ($query) => $query->withoutGlobalScopes(),
                'vehicle' => fn ($query) => $query->withoutGlobalScopes(),
                'company',
            ])
            ->where('status', ServiceOrderStatus::Delivered)
            ->whereNull('unpaid_delivery_alerted_at')
            ->whereNotNull('status_changed_at')
            ->where('status_changed_at', '<', $cutoff)
            ->whereHas('receipt', fn ($query) => $query->withoutGlobalScopes()->where('status', ReceiptStatus::Pending))
            ->lazyById()
            ->each(function (ServiceOrder $order) use ($notifier): void {
                $claimed = ServiceOrder::withoutGlobalScopes()
                    ->whereKey($order->id)
                    ->whereNull('unpaid_delivery_alerted_at')
                    ->update(['unpaid_delivery_alerted_at' => now()]);

                if ($claimed !== 1) {
                    return;
                }

                $notifier->orderDeliveredUnpaid($order);
                $this->info("Ordem {$order->id} avisada: entregue com recibo pendente.");
            });

        return self::SUCCESS;
    }
}
