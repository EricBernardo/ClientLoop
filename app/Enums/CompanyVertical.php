<?php

namespace App\Enums;

enum CompanyVertical: string
{
    case PetShop = 'pet_shop';
    case Automotive = 'automotive';

    public function label(): string
    {
        return match ($this) {
            self::PetShop => 'Pet shop',
            self::Automotive => 'Serviços automotivos',
        };
    }

    public function customerSingular(): string
    {
        return match ($this) {
            self::PetShop => 'responsável',
            self::Automotive => 'cliente',
        };
    }

    public function customerPlural(): string
    {
        return match ($this) {
            self::PetShop => 'Responsáveis',
            self::Automotive => 'Clientes',
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
