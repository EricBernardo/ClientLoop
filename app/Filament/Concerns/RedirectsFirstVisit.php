<?php

namespace App\Filament\Concerns;

use App\Support\FirstVisitGuide;

trait RedirectsFirstVisit
{
    protected function getRedirectUrl(): string
    {
        $company = auth()->user()?->company;

        if ($company && $company->setup_wizard_completed_at === null) {
            return FirstVisitGuide::url($company);
        }

        return parent::getRedirectUrl();
    }
}
