<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\Opportunity;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class DemoClinicSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::firstOrCreate(
            ['name' => 'Plano demonstração'],
            ['contact_limit' => 500, 'task_limit' => 1000, 'is_default' => false],
        );

        $company = Company::updateOrCreate(
            ['slug' => 'clinica-sorriso-demo'],
            [
                'name' => 'Clínica Sorriso & Saúde',
                'timezone' => 'America/Sao_Paulo',
                'status' => 'active',
                'confirmation_hours' => 24,
                'reactivation_months' => 6,
                'follow_up_days' => [1, 3, 7],
            ],
        );

        CompanySubscription::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $company->id],
            ['plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now()->subMonth()],
        );

        User::updateOrCreate(
            ['email' => 'demo@clientloop.test'],
            [
                'company_id' => $company->id,
                'name' => 'Marina Costa',
                'email_verified_at' => now(),
                'password' => Hash::make('clientloop123'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'admin@clientloop.test'],
            [
                'name' => 'Administração ClientLoop',
                'email_verified_at' => now(),
                'password' => Hash::make('clientloop123'),
                'is_super_admin' => true,
            ],
        );

        $services = collect([
            ['name' => 'Avaliação odontológica', 'suggested_price' => 180, 'return_interval_months' => 6],
            ['name' => 'Limpeza e prevenção', 'suggested_price' => 250, 'return_interval_months' => 6],
            ['name' => 'Clareamento dental', 'suggested_price' => 1200, 'return_interval_months' => 12],
            ['name' => 'Implante dentário', 'suggested_price' => 4500, 'return_interval_months' => 12],
        ])->mapWithKeys(fn (array $data) => [
            $data['name'] => Service::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $data['name']],
                [...$data, 'company_id' => $company->id, 'active' => true],
            ),
        ]);

        $customers = collect([
            ['name' => 'Ana Beatriz Lima', 'phone' => '5511998765432', 'email' => 'ana.lima@example.test', 'tags' => ['recall', 'prevenção'], 'last_activity_at' => now()->subMonths(7), 'next_return_at' => now()->subWeeks(2)],
            ['name' => 'Carlos Eduardo Alves', 'phone' => '5511987654321', 'email' => 'carlos.alves@example.test', 'tags' => ['novo lead'], 'last_activity_at' => now()->subDay()],
            ['name' => 'Fernanda Souza', 'phone' => '5511976543210', 'email' => 'fernanda.souza@example.test', 'tags' => ['clareamento'], 'last_activity_at' => now()->subMonths(3), 'next_return_at' => now()->addMonth()],
            ['name' => 'João Pedro Martins', 'phone' => '5511965432109', 'email' => 'joao.martins@example.test', 'tags' => ['reativação'], 'last_activity_at' => now()->subMonths(9)],
            ['name' => 'Luciana Ribeiro', 'phone' => '5511954321098', 'email' => 'luciana.ribeiro@example.test', 'tags' => ['VIP'], 'last_activity_at' => now()->subMonths(2)],
            ['name' => 'Paulo Henrique Reis', 'phone' => '5511943210987', 'email' => 'paulo.reis@example.test', 'tags' => ['não contatar'], 'last_activity_at' => now()->subYear(), 'opted_out_at' => now()->subMonth(), 'opt_out_note' => 'Solicitou não receber mensagens.'],
        ])->mapWithKeys(fn (array $data) => [
            $data['name'] => Customer::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'phone' => $data['phone']],
                [...$data, 'company_id' => $company->id],
            ),
        ]);

        $appointments = [
            ['external_id' => 'demo-agenda-001', 'customer' => 'Ana Beatriz Lima', 'service' => 'Limpeza e prevenção', 'scheduled_at' => now()->addDay()->setTime(10, 0), 'status' => 'scheduled', 'potential_value' => 250, 'next_return_at' => now()->addMonths(6)],
            ['external_id' => 'demo-agenda-002', 'customer' => 'Fernanda Souza', 'service' => 'Clareamento dental', 'scheduled_at' => now()->addDays(3)->setTime(14, 30), 'status' => 'confirmed', 'potential_value' => 1200],
            ['external_id' => 'demo-agenda-003', 'customer' => 'Luciana Ribeiro', 'service' => 'Avaliação odontológica', 'scheduled_at' => now()->subDays(4)->setTime(9, 0), 'status' => 'completed', 'potential_value' => 180, 'realized_value' => 180],
        ];

        $createdAppointments = collect($appointments)->mapWithKeys(function (array $data) use ($company, $customers, $services) {
            $appointment = Appointment::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'external_id' => $data['external_id']],
                [
                    'customer_id' => $customers[$data['customer']]->id,
                    'service_id' => $services[$data['service']]->id,
                    'scheduled_at' => $data['scheduled_at'],
                    'status' => $data['status'],
                    'potential_value' => $data['potential_value'],
                    'realized_value' => $data['realized_value'] ?? null,
                    'next_return_at' => $data['next_return_at'] ?? null,
                    'origin' => 'manual',
                ],
            );

            return [$data['external_id'] => $appointment];
        });

        $templates = collect([
            ['name' => 'Confirmação de agenda', 'type' => 'confirmation', 'body' => 'Olá, {{cliente}}! Passando para confirmar seu agendamento de {{servico}} em {{data}} às {{horario}}. Podemos confirmar sua presença?'],
            ['name' => 'Retorno preventivo', 'type' => 'recall', 'body' => 'Olá, {{cliente}}! Já está na hora do seu retorno preventivo. Queremos cuidar do seu sorriso. Posso verificar os próximos horários?'],
            ['name' => 'Reativação', 'type' => 'reactivation', 'body' => 'Olá, {{cliente}}! Sentimos sua falta na {{empresa}}. Temos horários disponíveis para você retomar seus cuidados. Quer agendar uma avaliação?'],
        ])->mapWithKeys(fn (array $data) => [$data['name'] => MessageTemplate::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $company->id, 'name' => $data['name']],
            [...$data, 'company_id' => $company->id, 'active' => true],
        )]);

        $opportunities = [
            ['customer' => 'Carlos Eduardo Alves', 'service' => 'Implante dentário', 'title' => 'Orçamento de implante unitário', 'stage' => 'proposal', 'urgency' => 'high', 'potential_value' => 4500, 'next_follow_up_at' => now()->addDay(), 'notes' => 'Solicitou condições de pagamento.'],
            ['customer' => 'Fernanda Souza', 'service' => 'Clareamento dental', 'title' => 'Clareamento para casamento', 'stage' => 'scheduling', 'urgency' => 'normal', 'potential_value' => 1200, 'next_follow_up_at' => now()->addDays(2), 'notes' => 'Quer concluir antes do casamento.'],
            ['customer' => 'Luciana Ribeiro', 'service' => 'Avaliação odontológica', 'title' => 'Avaliação concluída', 'stage' => 'won', 'urgency' => 'normal', 'potential_value' => 180, 'realized_value' => 180, 'appointment_id' => $createdAppointments['demo-agenda-003']->id],
        ];

        collect($opportunities)->each(function (array $data) use ($company, $customers, $services): void {
            Opportunity::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'title' => $data['title']],
                [
                    ...Arr::except($data, ['customer', 'service']),
                    'company_id' => $company->id,
                    'customer_id' => $customers[$data['customer']]->id,
                    'service_id' => $services[$data['service']]->id,
                ],
            );
        });

        $campaign = Campaign::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Reativação - pacientes inativos'],
            ['type' => 'reactivation', 'status' => 'draft', 'message_template_id' => $templates['Reativação']->id, 'filters' => ['months_inactive' => 6]],
        );

        $tasks = [
            ['customer' => 'Ana Beatriz Lima', 'appointment_id' => $createdAppointments['demo-agenda-001']->id, 'template' => 'Confirmação de agenda', 'type' => 'confirmation', 'priority' => 'high', 'due_at' => now(), 'message' => 'Olá, Ana Beatriz Lima! Passando para confirmar seu agendamento de Limpeza e prevenção amanhã às 10:00. Podemos confirmar sua presença?'],
            ['customer' => 'Carlos Eduardo Alves', 'template' => null, 'type' => 'follow_up', 'priority' => 'high', 'due_at' => now()->addDay(), 'message' => 'Olá, Carlos! Conseguiu avaliar a proposta do implante? Posso ajudar com alguma dúvida?'],
            ['customer' => 'João Pedro Martins', 'template' => 'Reativação', 'type' => 'reactivation', 'priority' => 'normal', 'due_at' => now()->subHour(), 'message' => 'Olá, João Pedro Martins! Sentimos sua falta na Clínica Sorriso & Saúde. Quer agendar uma avaliação?'],
            ['customer' => 'Ana Beatriz Lima', 'template' => 'Retorno preventivo', 'type' => 'recall', 'priority' => 'normal', 'due_at' => now()->subDay(), 'status' => 'completed', 'outcome' => 'scheduled', 'completed_at' => now()->subHours(20), 'message' => 'Olá, Ana Beatriz Lima! Já está na hora do seu retorno preventivo. Posso verificar os próximos horários?'],
        ];

        foreach ($tasks as $data) {
            ContactTask::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'customer_id' => $customers[$data['customer']]->id, 'type' => $data['type'], 'rendered_message' => $data['message']],
                [
                    'appointment_id' => $data['appointment_id'] ?? null,
                    'message_template_id' => $data['template'] ? $templates[$data['template']]->id : null,
                    'campaign_id' => $data['type'] === 'reactivation' ? $campaign->id : null,
                    'status' => $data['status'] ?? 'pending',
                    'priority' => $data['priority'],
                    'due_at' => $data['due_at'],
                    'outcome' => $data['outcome'] ?? null,
                    'completed_at' => $data['completed_at'] ?? null,
                ],
            );
        }
    }
}
