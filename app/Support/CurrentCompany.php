<?php

namespace App\Support;

use App\Models\Company;

final class CurrentCompany
{
    public static function get(): ?Company
    {
        $company = auth()->user()?->company;

        return $company instanceof Company ? $company : null;
    }

    public static function isPetShop(): bool
    {
        return self::get()?->isPetShop() === true;
    }

    public static function isAutomotive(): bool
    {
        return self::get()?->isAutomotive() === true;
    }
}
