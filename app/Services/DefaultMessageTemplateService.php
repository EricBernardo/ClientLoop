<?php

namespace App\Services;

use App\Models\Company;
use App\Models\MessageTemplate;
use Illuminate\Support\Collection;

class DefaultMessageTemplateService
{
    /**
     * @return list<array{name: string, type: string, body: string}>
     */
    public function defaults(): array
    {
        return [
            [
                'name' => 'Confirmação de banho',
                'type' => 'confirmation',
                'body' => 'Olá, {{responsavel}}! O banho de {{pet}} está marcado para {{data}} às {{horario}}. Podemos confirmar?',
            ],
            [
                'name' => 'Hora de voltar',
                'type' => 'recall',
                'body' => 'Olá, {{responsavel}}! Sentimos falta de {{pet}} na {{empresa}}. Quer agendar um banho?',
            ],
            [
                'name' => 'Reativação',
                'type' => 'reactivation',
                'body' => 'Olá, {{responsavel}}! Faz um tempo que não vemos {{pet}}. Quer reservar um horário?',
            ],
        ];
    }

    /**
     * @return Collection<string, MessageTemplate>
     */
    public function provision(Company $company): Collection
    {
        return collect($this->defaults())->mapWithKeys(
            fn (array $data): array => [
                $data['name'] => MessageTemplate::withoutGlobalScopes()->updateOrCreate(
                    ['company_id' => $company->id, 'name' => $data['name']],
                    [...$data, 'company_id' => $company->id, 'active' => true],
                ),
            ],
        );
    }
}
