<?php

namespace App\Enums;

enum ServiceOrderStatus: string
{
    case Open = 'aberta';
    case InProgress = 'em_andamento';
    case Ready = 'pronta';
    case Delivered = 'entregue';
    case Cancelled = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta',
            self::InProgress => 'Em andamento',
            self::Ready => 'Pronta',
            self::Delivered => 'Entregue',
            self::Cancelled => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::InProgress => 'warning',
            self::Ready => 'success',
            self::Delivered => 'gray',
            self::Cancelled => 'danger',
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
