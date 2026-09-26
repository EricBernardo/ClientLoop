<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Calendar;
use App\Filament\Resources\ContactTasks\ContactTaskResource;
use App\Filament\Resources\PetPackages\PetPackageResource;
use App\Models\Appointment;
use App\Models\ContactTask;
use App\Models\PetPackage;
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

        $today = now()->startOfDay();
        $todayAppointments = Appointment::query()->whereDate('scheduled_at', $today)->whereNotIn('status', ['cancelled', 'no_show'])->count();
        $pendingTasks = ContactTask::query()->where('status', 'pending')->count();
        $lowPackages = PetPackage::query()->get()->filter(fn (PetPackage $package): bool => $package->remaining_credits <= 1 && $package->payment_status === 'paid')->count();
        $expiredPackages = PetPackage::query()->whereDate('valid_until', '<', today())->count();
        $nextPackageSteps = PetPackage::query()->where('payment_status', 'paid')->get()->filter(fn (PetPackage $package): bool => app(PackageService::class)->nextItem($package) !== null)->count();
        $usage = app(QuotaService::class)->usage($company);

        return [
            Stat::make('Agenda de hoje', $todayAppointments)->description('Ver os atendimentos de hoje')->url(Calendar::getUrl(['date' => today()->toDateString(), 'mode' => 'day']))->color($todayAppointments ? 'primary' : 'success'),
            Stat::make('Tarefas pendentes', $pendingTasks)->description('Abrir contatos que precisam de ação')->url(ContactTaskResource::getUrl('index', ['view' => 'pending']))->color($pendingTasks ? 'warning' : 'success'),
            Stat::make('Uso do plano', $usage['contacts'].'/'.$usage['contact_limit'].' · '.$usage['tasks'].'/'.$usage['task_limit'])
                ->description('Responsáveis e tarefas no mês')
                ->color($usage['remaining_tasks'] === 0 || $usage['contacts'] >= $usage['contact_limit'] ? 'danger' : 'success'),
            Stat::make('Pacotes com pouco saldo', $lowPackages)->description('Ver pacotes com até um crédito')->url(PetPackageResource::getUrl('index', ['view' => 'low']))->color($lowPackages ? 'warning' : 'success'),
            Stat::make('Próximas etapas de pacote', $nextPackageSteps)->description('Ver pacotes que ainda têm atendimento')->url(PetPackageResource::getUrl('index'))->color($nextPackageSteps ? 'primary' : 'success'),
            Stat::make('Pacotes vencidos', $expiredPackages)->description('Ver pacotes fora da validade')->url(PetPackageResource::getUrl('index', ['view' => 'expired']))->color($expiredPackages ? 'danger' : 'success'),
        ];
    }
}
