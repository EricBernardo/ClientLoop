<?php

namespace App\Filament\Super\Widgets;

use App\Filament\Super\Resources\Companies\CompanyResource;
use App\Support\SignupMonitor;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SignupOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -4;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $counts = SignupMonitor::counts();

        return [
            Stat::make('Em teste', $counts['trial'])
                ->description('Lojas no período de teste')
                ->url($this->companiesUrl(['status' => ['value' => 'trial']]))
                ->color($counts['trial'] ? 'warning' : 'success'),
            Stat::make('Teste acaba em 3 dias', $counts['ending'])
                ->description('Vence nos próximos 3 dias')
                ->url($this->companiesUrl(['trial_ending' => ['isActive' => true]]))
                ->color($counts['ending'] ? 'danger' : 'success'),
            Stat::make('Cadastros em 7 dias', $counts['recent'])
                ->description('Lojas criadas na última semana')
                ->url($this->companiesUrl(['recent' => ['isActive' => true]]))
                ->color('primary'),
            Stat::make('Sem uso', $counts['unused'])
                ->description('Teste sem responsável e sem agendamento')
                ->url($this->companiesUrl(['unused' => ['isActive' => true]]))
                ->color($counts['unused'] ? 'warning' : 'success'),
            Stat::make('Cota cheia', $counts['quota'])
                ->description('Limite de responsáveis ou tarefas no mês')
                ->url($this->companiesUrl(['quota' => ['isActive' => true]]))
                ->color($counts['quota'] ? 'danger' : 'success'),
            Stat::make('Suspensas', $counts['suspended'])
                ->description('Lojas com acesso suspenso')
                ->url($this->companiesUrl(['status' => ['value' => 'suspended']]))
                ->color($counts['suspended'] ? 'danger' : 'success'),
        ];
    }

    /** @param  array<string, array<string, mixed>>  $filters */
    private function companiesUrl(array $filters): string
    {
        return CompanyResource::getUrl('index', ['tableFilters' => $filters], panel: 'super');
    }
}
