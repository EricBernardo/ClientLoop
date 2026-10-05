<?php

namespace App\Filament\Concerns;

use App\Support\CurrentCompany;

trait LimitsToPetShop
{
    public static function canAccess(): bool
    {
        return parent::canAccess() && CurrentCompany::isPetShop();
    }
}
