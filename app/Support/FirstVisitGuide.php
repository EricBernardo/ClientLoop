<?php

namespace App\Support;

use App\Filament\Pages\BusinessSettings;
use App\Filament\Pages\Calendar;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\ContactTasks\ContactTaskResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Pets\PetResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use Illuminate\Http\Request;

class FirstVisitGuide
{
    public const QUEUE_SESSION = 'first_visit_queue_seen';

    /**
     * @var array<string, string>
     */
    public const STEPS = [
        'hours' => 'Horários',
        'service' => 'Serviço',
        'customer' => 'Responsável',
        'pet' => 'Pet',
        'appointment' => 'Agenda',
        'queue' => 'Fila',
        'review' => 'Concluir',
    ];

    /**
     * @return array{key: string, title: string, body: string, url: string, prefixes: list<string>, can_finish: bool}
     */
    public static function current(Company $company): array
    {
        if ($company->hours_configured_at === null) {
            return self::step(
                'hours',
                'Passo 1 de 7 — Horários',
                'Confirme os dias e o expediente e salve. A agenda só abre horários dentro disso.',
                BusinessSettings::getUrl(panel: 'company'),
                ['admin/business-settings'],
            );
        }

        if (! Service::query()->where('company_id', $company->id)->exists()) {
            return self::step(
                'service',
                'Passo 2 de 7 — Serviço',
                'Cadastre o que você faz, por exemplo Banho, com a duração em minutos. Se preencher o retorno em meses, ao concluir um atendimento o sistema prevê a próxima visita.',
                ServiceResource::getUrl('create', panel: 'company'),
                ['admin/services', 'admin/services/*'],
            );
        }

        if (! Customer::query()->where('company_id', $company->id)->exists()) {
            return self::step(
                'customer',
                'Passo 3 de 7 — Responsável',
                'Cadastre a pessoa que recebe o WhatsApp. Nome e telefone bastam.',
                CustomerResource::getUrl('create', panel: 'company'),
                ['admin/customers', 'admin/customers/*'],
            );
        }

        if (! Pet::query()->where('company_id', $company->id)->exists()) {
            $customerId = Customer::query()->where('company_id', $company->id)->value('id');
            $url = PetResource::getUrl('create', panel: 'company');

            if ($customerId) {
                $url .= '?customer_id='.$customerId;
            }

            return self::step(
                'pet',
                'Passo 4 de 7 — Pet',
                'Cadastre o pet desse responsável. É ele que entra na agenda.',
                $url,
                ['admin/pets', 'admin/pets/*'],
            );
        }

        if (! Appointment::query()->where('company_id', $company->id)->exists()) {
            return self::step(
                'appointment',
                'Passo 5 de 7 — Agenda',
                'Clique num horário livre e marque o pet com o serviço. Esse é o mesmo caminho de todo dia.',
                Calendar::getUrl(panel: 'company'),
                ['admin/calendar', 'admin/calendar/*', 'admin/appointments', 'admin/appointments/*'],
            );
        }

        if ((int) session(self::QUEUE_SESSION) !== $company->id) {
            $hours = $company->confirmation_hours ?? 24;

            return self::step(
                'queue',
                'Passo 6 de 7 — Fila de contatos',
                "A confirmação deste primeiro horário já está aqui, com a mensagem de WhatsApp pronta. No dia a dia ela aparece cerca de {$hours} horas antes. Abra “WhatsApp e registrar” para ver o texto.",
                ContactTaskResource::getUrl('index', panel: 'company'),
                ['admin/contact-tasks', 'admin/contact-tasks/*'],
            );
        }

        $completed = Appointment::query()->where('company_id', $company->id)->where('status', 'completed')->exists();

        if ($completed) {
            return self::step(
                'review',
                'Primeiro atendimento feito',
                'O atendimento foi concluído. Se o serviço tem retorno em meses, a data aparece no responsável. Pacotes e o guia completo ficam no painel.',
                AppointmentResource::getUrl('index', panel: 'company'),
                ['admin/appointments', 'admin/appointments/*'],
                true,
            );
        }

        return self::step(
            'review',
            'Passo 7 de 7 — Concluir',
            'Abra o atendimento e clique em Concluir atendimento. O crédito de pacote só baixa aqui. Se o serviço tem retorno em meses, a data prevista vai para o responsável.',
            AppointmentResource::getUrl('index', panel: 'company'),
            ['admin/appointments', 'admin/appointments/*'],
        );
    }

    public static function url(Company $company): string
    {
        return self::current($company)['url'];
    }

    /**
     * @param  array{key: string, title: string, body: string, url: string, prefixes: list<string>, can_finish: bool}  $step
     */
    public static function matches(Request $request, array $step): bool
    {
        return $request->is($step['prefixes']);
    }

    /**
     * @param  list<string>  $prefixes
     * @return array{key: string, title: string, body: string, url: string, prefixes: list<string>, can_finish: bool}
     */
    private static function step(string $key, string $title, string $body, string $url, array $prefixes, bool $canFinish = false): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'prefixes' => $prefixes,
            'can_finish' => $canFinish,
        ];
    }
}
