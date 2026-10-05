<?php

namespace App\Enums;

enum CashExpenseCategory: string
{
    case Rent = 'aluguel';
    case Parts = 'pecas';
    case Payroll = 'salario';
    case Other = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Rent => 'Aluguel',
            self::Parts => 'Peças',
            self::Payroll => 'Salário',
            self::Other => 'Outro',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
