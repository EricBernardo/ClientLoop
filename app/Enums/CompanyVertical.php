<?php

namespace App\Enums;

enum CompanyVertical: string
{
    case PetShop = 'pet_shop';
    case Automotive = 'automotive';

    public const string RememberedNicheCookie = 'clientloop_niche';

    public const int RememberedNicheCookieMinutes = 60 * 24 * 365;

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

    public function landingRoute(): string
    {
        return match ($this) {
            self::PetShop => 'landing.pet',
            self::Automotive => 'landing.automotive',
        };
    }

    public static function fromCookie(mixed $value): ?self
    {
        if (! is_string($value)) {
            return null;
        }

        return self::tryFrom($value);
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
