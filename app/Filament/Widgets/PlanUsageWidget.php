<?php

namespace App\Filament\Widgets;

use App\Services\QuotaService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlanUsageWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -10;

    protected function getStats(): array
    {
        $company = auth()->user()?->company;
        if (! $company) {
            return [];
        }

        $usage = app(QuotaService::class)->usage($company);
        $contactsLeft = max(0, $usage['contact_limit'] - $usage['contacts']);
        $tasksLeft = $usage['remaining_tasks'];

        return [
            Stat::make('Responsáveis no mês', $usage['contacts'].' / '.$usage['contact_limit'])
                ->description($contactsLeft === 0 ? 'Limite atingido' : "Restam {$contactsLeft}")
                ->color($contactsLeft === 0 ? 'danger' : ($contactsLeft <= 10 ? 'warning' : 'success')),
            Stat::make('Tarefas no mês', $usage['tasks'].' / '.$usage['task_limit'])
                ->description($tasksLeft === 0 ? 'Limite atingido' : "Restam {$tasksLeft}")
                ->color($tasksLeft === 0 ? 'danger' : ($tasksLeft <= 20 ? 'warning' : 'success')),
        ];
    }
}
