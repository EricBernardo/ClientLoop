<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Support\SetupChecklist;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class SetupChecklistWidget extends Widget
{
    protected string $view = 'filament.widgets.setup-checklist';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -20;

    public static function canView(): bool
    {
        $company = auth()->user()?->company;

        return $company instanceof Company && SetupChecklist::shouldShow($company);
    }

    /** @return list<array{key:string,label:string,done:bool,url:string}> */
    public function getStepsProperty(): array
    {
        $company = auth()->user()?->company;

        return $company ? SetupChecklist::steps($company) : [];
    }

    public function dismiss(): void
    {
        $company = auth()->user()?->company;
        if (! $company) {
            return;
        }

        $company->update(['onboarding_completed_at' => now()]);

        Notification::make()
            ->success()
            ->title('Checklist ocultado')
            ->body('Você pode reabrir o guia em “Como usar” a qualquer momento.')
            ->send();

        $this->dispatch('$refresh');
    }
}
