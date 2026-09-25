<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use InvalidArgumentException;

class TemplateRenderer
{
    public const VARIABLES = ['cliente', 'responsavel', 'pet', 'empresa', 'servico', 'data', 'horario', 'link_agendamento'];

    public function render(string $body, Customer $customer, ?Service $service = null, ?\DateTimeInterface $date = null, ?Pet $pet = null): string
    {
        $this->validate($body);
        $company = $customer->company;
        $date = $date ?: now();
        $map = ['cliente' => $customer->name, 'responsavel' => $customer->name, 'pet' => $pet?->name ?? '', 'empresa' => $company->name, 'servico' => $service?->name ?? '', 'data' => $date->format('d/m/Y'), 'horario' => $date->format('H:i'), 'link_agendamento' => ''];

        return preg_replace_callback('/\{\{\s*([^}\s]+)\s*\}\}/', fn ($m) => $map[$m[1]], $body);
    }

    public function validate(string $body): void
    {
        preg_match_all('/\{\{\s*([^}\s]+)\s*\}\}/', $body, $matches);
        foreach ($matches[1] as $name) {
            if (! in_array($name, self::VARIABLES, true)) {
                throw new InvalidArgumentException("Variável não permitida: {$name}");
            }
        }
    }
}
