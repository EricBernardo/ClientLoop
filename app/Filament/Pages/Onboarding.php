<?php

namespace App\Filament\Pages;

use App\Support\FirstVisitGuide;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;

class Onboarding extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'setup';

    protected static ?string $title = 'Configuração da loja';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected string $view = 'filament.pages.onboarding';

    public function mount(): void
    {
        $company = auth()->user()->company;

        if ($company->setup_wizard_completed_at !== null) {
            $this->redirect(Dashboard::getUrl(panel: 'company'));

            return;
        }

        if (auth()->user()->role !== 'attendant') {
            $this->redirect(FirstVisitGuide::url($company));
        }
    }
}
