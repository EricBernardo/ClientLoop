<?php

namespace App\Enums;

enum ReceiptPaymentMethod: string
{
    case Cash = 'dinheiro';
    case Pix = 'pix';
    case Card = 'cartao';
    case OnAccount = 'a_prazo';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Dinheiro',
            self::Pix => 'Pix',
            self::Card => 'Cartão',
            self::OnAccount => 'A prazo',
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
