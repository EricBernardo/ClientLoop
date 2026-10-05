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
    public function defaultsFor(Company $company): array
    {
        if ($company->isAutomotive()) {
            return [
                [
                    'name' => 'Confirmação de horário',
                    'type' => 'confirmation',
                    'body' => 'Olá, {{cliente}}! O {{servico}} do {{veiculo}} está marcado para {{data}} às {{horario}}. Podemos confirmar? Se preferir, responda pelo link: {{link_confirmacao}} — ou agende outro horário: {{link_agendamento}}',
                ],
                [
                    'name' => 'Hora de voltar',
                    'type' => 'recall',
                    'body' => 'Olá, {{cliente}}! Já está na hora de revisar o {{veiculo}} na {{empresa}}. Quer agendar? {{link_agendamento}}',
                ],
                [
                    'name' => 'Reativação',
                    'type' => 'reactivation',
                    'body' => 'Olá, {{cliente}}! Faz um tempo que o {{veiculo}} não passa na {{empresa}}. Quer reservar um horário? {{link_agendamento}}',
                ],
            ];
        }

        return $this->defaults();
    }

    public function defaults(): array
    {
        return [
            [
                'name' => 'Confirmação de banho',
                'type' => 'confirmation',
                'body' => 'Olá, {{responsavel}}! O banho de {{pet}} está marcado para {{data}} às {{horario}}. Podemos confirmar? Se preferir, responda pelo link: {{link_confirmacao}} — ou agende outro horário: {{link_agendamento}}',
            ],
            [
                'name' => 'Hora de voltar',
                'type' => 'recall',
                'body' => 'Olá, {{responsavel}}! Sentimos falta de {{pet}} na {{empresa}}. Quer agendar um banho? {{link_agendamento}}',
            ],
            [
                'name' => 'Reativação',
                'type' => 'reactivation',
                'body' => 'Olá, {{responsavel}}! Faz um tempo que não vemos {{pet}}. Quer reservar um horário? {{link_agendamento}}',
            ],
        ];
    }

    /**
     * @return Collection<string, MessageTemplate>
     */
    public function provision(Company $company): Collection
    {
        return collect($this->defaultsFor($company))->mapWithKeys(
            fn (array $data): array => [
                $data['name'] => MessageTemplate::withoutGlobalScopes()->updateOrCreate(
                    ['company_id' => $company->id, 'name' => $data['name']],
                    [...$data, 'company_id' => $company->id, 'active' => true],
                ),
            ],
        );
    }
}
