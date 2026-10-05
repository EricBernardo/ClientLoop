<?php

namespace App\Filament\Concerns;

use App\Support\CurrentCompany;

trait LimitsToAutomotive
{
    public static function canAccess(): bool
    {
        return parent::canAccess() && CurrentCompany::isAutomotive();
    }
}
