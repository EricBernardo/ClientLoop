<?php

namespace App\Filament\Widgets;

use App\Enums\CashEntryDirection;
use App\Enums\ReceiptStatus;
use App\Enums\ServiceOrderStatus;
use App\Filament\Pages\Calendar;
use App\Filament\Pages\CashFlow;
use App\Filament\Resources\ContactTasks\ContactTaskResource;
use App\Filament\Resources\PetPackages\PetPackageResource;
use App\Filament\Resources\ServiceOrders\ServiceOrderResource;
use App\Models\Appointment;
use App\Models\CashEntry;
use App\Models\ContactTask;
use App\Models\PetPackage;
use App\Models\ServiceOrder;
use App\Models\ServiceReceipt;
use App\Services\PackageService;
use App\Services\QuotaService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CompanyOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $company = auth()->user()?->company;
        if (! $company) {
            return [];
        }

        if ($company->isAutomotive()) {
            return $this->automotiveStats();
        }

        $today = now()->startOfDay();
        $todayAppointments = Appointment::query()->whereDate('scheduled_at', $today)->whereNotIn('status', ['cancelled', 'no_show'])->count();
        $pendingTasks = ContactTask::query()->where('status', 'pending')->count();
        $lateTasks = ContactTask::query()->where('status', 'pending')->where('due_at', '<', now())->count();
        $staleOpenTasks = ContactTask::query()->where('status', 'pending')->where('updated_at', '<', now()->subHours(4))->count();
        $lowPackages = PetPackage::query()->get()->filter(fn (PetPackage $package): bool => $package->remaining_credits <= 1 && $package->payment_status === 'paid')->count();
        $expiredPackages = PetPackage::query()->whereDate('valid_until', '<', today())->count();
        $nextPackageSteps = PetPackage::query()->where('payment_status', 'paid')->get()->filter(fn (PetPackage $package): bool => app(PackageService::class)->nextItem($package) !== null)->count();
        $usage = app(QuotaService::class)->usage($company);
        $quotaExhausted = $usage['remaining_tasks'] === 0;

        return [
            Stat::make('Agenda de hoje', $todayAppointments)->description('Ver os atendimentos de hoje')->url(Calendar::getUrl(['date' => today()->toDateString(), 'mode' => 'day']))->color($todayAppointments ? 'primary' : 'success'),
            Stat::make('Tarefas pendentes', $pendingTasks)->description('Abrir contatos que precisam de ação')->url(ContactTaskResource::getUrl('index', ['view' => 'pending']))->color($pendingTasks ? 'warning' : 'success'),
            Stat::make('Tarefas atrasadas', $lateTasks)
                ->description($staleOpenTasks ? "{$staleOpenTasks} sem resultado há mais de 4h" : 'Vencimento já passou')
                ->url(ContactTaskResource::getUrl('index', ['view' => 'late']))
                ->color($lateTasks || $staleOpenTasks ? 'danger' : 'success'),
            Stat::make('Uso do plano', $usage['contacts'].'/'.$usage['contact_limit'].' · '.$usage['tasks'].'/'.$usage['task_limit'])
                ->description($quotaExhausted ? 'Cota de tarefas esgotada — confirmações não serão geradas' : 'Responsáveis e tarefas no mês')
                ->color($quotaExhausted || $usage['contacts'] >= $usage['contact_limit'] ? 'danger' : 'success'),
            Stat::make('Pacotes com pouco saldo', $lowPackages)->description('Ver pacotes com até um crédito')->url(PetPackageResource::getUrl('index', ['view' => 'low']))->color($lowPackages ? 'warning' : 'success'),
            Stat::make('Próximas etapas de pacote', $nextPackageSteps)->description('Ver pacotes que ainda têm atendimento')->url(PetPackageResource::getUrl('index'))->color($nextPackageSteps ? 'primary' : 'success'),
            Stat::make('Pacotes vencidos', $expiredPackages)->description('Ver pacotes fora da validade')->url(PetPackageResource::getUrl('index', ['view' => 'expired']))->color($expiredPackages ? 'danger' : 'success'),
        ];
    }

    /** @return array<int, Stat> */
    private function automotiveStats(): array
    {
        $ready = ServiceOrder::query()->where('status', ServiceOrderStatus::Ready)->count();
        $inProgress = ServiceOrder::query()->where('status', ServiceOrderStatus::InProgress)->count();
        $queued = ServiceOrder::query()->where('status', ServiceOrderStatus::Open)->count();
        $pendingReceipts = ServiceReceipt::query()->where('status', ReceiptStatus::Pending);
        $pendingCount = (clone $pendingReceipts)->count();
        $pendingAmount = (float) (clone $pendingReceipts)->sum('amount');

        $from = now()->startOfMonth()->toDateString();
        $until = now()->endOfMonth()->toDateString();
        $income = (float) CashEntry::query()
            ->where('direction', CashEntryDirection::Income)
            ->whereDate('occurred_on', '>=', $from)
            ->whereDate('occurred_on', '<=', $until)
            ->sum('amount');
        $expense = (float) CashEntry::query()
            ->where('direction', CashEntryDirection::Expense)
            ->whereDate('occurred_on', '>=', $from)
            ->whereDate('occurred_on', '<=', $until)
            ->sum('amount');
        $balance = $income - $expense;
        $todayAppointments = Appointment::query()->whereDate('scheduled_at', today())->whereNotIn('status', ['cancelled', 'no_show'])->count();
        $pendingTasks = ContactTask::query()->where('status', 'pending')->count();

        return [
            Stat::make('Agenda de hoje', (string) $todayAppointments)
                ->description('Horários marcados para hoje')
                ->url(Calendar::getUrl(['date' => today()->toDateString(), 'mode' => 'day']))
                ->color($todayAppointments ? 'primary' : 'gray'),
            Stat::make('Tarefas pendentes', (string) $pendingTasks)
                ->description('Confirmações e retornos para o WhatsApp')
                ->url(ContactTaskResource::getUrl('index'))
                ->color($pendingTasks ? 'warning' : 'success'),
            Stat::make('Prontas para entrega', (string) $ready)
                ->description('Veículos prontos para o cliente retirar')
                ->url($this->ordersUrl(ServiceOrderStatus::Ready))
                ->color($ready > 0 ? 'success' : 'gray'),
            Stat::make('Em andamento', (string) $inProgress)
                ->description('Serviços acontecendo agora')
                ->url($this->ordersUrl(ServiceOrderStatus::InProgress))
                ->color($inProgress > 0 ? 'warning' : 'gray'),
            Stat::make('Na fila', (string) $queued)
                ->description('Ordens ainda não iniciadas')
                ->url($this->ordersUrl(ServiceOrderStatus::Open))
                ->color($queued > 0 ? 'info' : 'gray'),
            Stat::make('A receber', $this->money($pendingAmount))
                ->description($pendingCount === 1 ? '1 recibo pendente' : "{$pendingCount} recibos pendentes")
                ->url(ServiceOrderResource::getUrl('index'))
                ->color($pendingAmount > 0 ? 'warning' : 'success'),
            Stat::make('Caixa do mês', $this->money($balance))
                ->description('Entradas '.$this->money($income).' · Saídas '.$this->money($expense))
                ->url(CashFlow::getUrl())
                ->color($balance < 0 ? 'danger' : 'success'),
        ];
    }

    private function ordersUrl(ServiceOrderStatus $status): string
    {
        return ServiceOrderResource::getUrl('index', [
            'tableFilters' => ['status' => ['value' => $status->value]],
        ]);
    }

    private function money(float $amount): string
    {
        return 'R$ '.number_format($amount, 2, ',', '.');
    }
}
