<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\TextInput;

class BrlMoneyInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->prefix('R$')
            ->inputMode('decimal')
            ->live(onBlur: true)
            ->rule('numeric')
            ->formatStateUsing(fn (mixed $state): ?string => static::formatValue($state))
            ->mutateStateForValidationUsing(fn (mixed $state): ?string => static::normalizeValue($state))
            ->dehydrateStateUsing(fn (mixed $state): ?string => static::normalizeValue($state))
            ->extraInputAttributes([
                'x-on:input.capture' => <<<'JS'
                    const digits = $el.value.replace(/\D/g, '');

                    if (digits === '') {
                        $el.value = '';
                        return;
                    }

                    $el.value = new Intl.NumberFormat('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }).format(Number(digits) / 100);
                JS,
            ]);
    }

    public static function normalizeValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        $value = trim(str_replace(['R$', ' '], '', (string) $value));

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (preg_match('/\.\d{3}$/', $value) === 1) {
            $value = str_replace('.', '', $value);
        }

        if (! is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    public static function formatValue(mixed $value): ?string
    {
        $normalized = is_numeric($value)
            ? number_format((float) $value, 2, '.', '')
            : static::normalizeValue($value);

        return $normalized === null
            ? null
            : number_format((float) $normalized, 2, ',', '.');
    }
}
