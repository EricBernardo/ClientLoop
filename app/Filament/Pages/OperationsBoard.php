<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Opportunity;
use Filament\Pages\Page;

class OperationsBoard extends Page
{
    protected static ?string $navigationLabel = 'Visão operacional';

    protected static ?string $title = 'Agenda e funil';

    protected string $view = 'filament.pages.operations-board';

    public function getAppointmentsProperty()
    {
        return Appointment::query()->with(['customer', 'service'])->whereBetween('scheduled_at', [now()->startOfDay(), now()->copy()->addDays(7)->endOfDay()])->orderBy('scheduled_at')->get();
    }

    public function getStagesProperty(): array
    {
        $stages = ['new' => 'Novo', 'qualification' => 'Triagem', 'proposal' => 'Orçamento', 'scheduling' => 'Aguardando agendamento', 'won' => 'Ganho', 'lost' => 'Perdido'];

        return collect($stages)->map(fn (string $label, string $stage) => ['label' => $label, 'items' => Opportunity::query()->with('customer')->where('stage', $stage)->latest()->get()])->all();
    }
}
