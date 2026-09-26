<?php

namespace App\Services;

use InvalidArgumentException;

class PhoneNormalizer
{
    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55'.$digits;
        } if (! preg_match('/^55\d{10,11}$/', $digits)) {
            throw new InvalidArgumentException('Informe um telefone brasileiro válido.');
        }

        return $digits;
    }
}
