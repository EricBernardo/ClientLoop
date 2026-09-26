<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use InvalidArgumentException;

class TemplateRenderer
{
    public const VARIABLES = ['responsavel', 'cliente', 'pet', 'empresa', 'servico', 'data', 'horario', 'link_agendamento', 'link_confirmacao'];

    public function render(
        string $body,
        Customer $customer,
        ?Service $service = null,
        ?\DateTimeInterface $date = null,
        ?Pet $pet = null,
        ?Company $company = null,
        ?string $confirmationUrl = null,
    ): string {
        $this->validate($body);
        $company ??= $customer->company;
        $date = $date ?: now();
        $bookingUrl = $company?->publicBookingUrl($customer->id) ?? url('/admin/appointments/create?customer_id='.$customer->id);
        $map = [
            'responsavel' => $customer->name,
            'cliente' => $customer->name,
            'pet' => $pet?->name ?? '',
            'empresa' => $company?->name ?? '',
            'servico' => $service?->name ?? '',
            'data' => $date->format('d/m/Y'),
            'horario' => $date->format('H:i'),
            'link_agendamento' => $bookingUrl,
            'link_confirmacao' => $confirmationUrl ?? '',
        ];

        return preg_replace_callback('/\{\{\s*([^}\s]+)\s*\}\}/', fn ($m) => $map[$m[1]] ?? '', $body) ?? $body;
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
