<?php

namespace App\Enums;

enum CashEntryDirection: string
{
    case Income = 'entrada';
    case Expense = 'saida';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Entrada',
            self::Expense => 'Saída',
        };
    }
}
