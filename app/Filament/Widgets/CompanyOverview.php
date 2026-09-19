<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\ContactTasks\ContactTaskResource;
use App\Filament\Resources\Opportunities\OpportunityResource;
use App\Models\Appointment;
use App\Models\ContactTask;
use App\Models\Opportunity;
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
        $risk = Appointment::query()->whereIn('status', ['scheduled', 'reschedule_requested'])->whereBetween('scheduled_at', [now(), now()->copy()->addDay()])->count();
        $pending = ContactTask::query()->where('status', 'pending')->whereDate('due_at', $today)->count();
        $late = ContactTask::query()->where('status', 'pending')->where('due_at', '<', now())->count();
        $completed = Appointment::query()->where('status', 'completed')->whereBetween('scheduled_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('realized_value');
        $activeOpportunities = Opportunity::query()->whereNotIn('stage', ['won', 'lost'])->sum('potential_value');
        $usage = app(QuotaService::class)->usage($company);

        return [
            Stat::make('Tarefas para hoje', $pending)->description('Ver tarefas com vencimento hoje')->url(ContactTaskResource::getUrl('index', ['view' => 'today']))->color($pending ? 'primary' : 'success'),
            Stat::make('Tarefas atrasadas', $late)->description('Ver tarefas pendentes vencidas')->url(ContactTaskResource::getUrl('index', ['view' => 'late']))->color($late ? 'danger' : 'success'),
            Stat::make('Agendamentos em risco', $risk)->description('Próximas 24 horas sem confirmação')->url(AppointmentResource::getUrl('index', ['view' => 'risk']))->color($risk ? 'warning' : 'success'),
            Stat::make('Receita realizada', 'R$ '.number_format((float) $completed, 2, ',', '.'))->description('Agendamentos concluídos no mês')->url(AppointmentResource::getUrl('index', ['view' => 'revenue-month']))->color('success'),
            Stat::make('Valor em negociação', 'R$ '.number_format((float) $activeOpportunities, 2, ',', '.'))->description('Oportunidades abertas')->url(OpportunityResource::getUrl('index', ['view' => 'active']))->color('primary'),
            Stat::make('Cota de tarefas', $usage['tasks'].' / '.$usage['task_limit'])->description($usage['remaining_tasks'].' restante(s) neste mês')->url(ContactTaskResource::getUrl('index', ['view' => 'month']))->color($usage['remaining_tasks'] ? 'gray' : 'danger'),
        ];
    }
}
