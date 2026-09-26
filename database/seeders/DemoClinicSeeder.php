<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\Groomer;
use App\Models\MessageTemplate;
use App\Models\PackageOffer;
use App\Models\PackageRedemption;
use App\Models\Pet;
use App\Models\PetPackage;
use App\Models\PetPackageItem;
use App\Models\Plan;
use App\Models\Service;
use App\Models\UsageRecord;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\DefaultMessageTemplateService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoClinicSeeder extends Seeder
{
    public function run(): void
    {
        $tz = 'America/Sao_Paulo';
        $openedAt = now($tz)->subMonths(18)->startOfDay();

        $plan = Plan::firstOrCreate(['name' => 'Plano demonstração'], ['contact_limit' => 500, 'task_limit' => 1000, 'is_default' => false]);
        $company = Company::updateOrCreate(['slug' => 'petshop-patinhas-demo'], [
            'name' => 'Pet Shop Patinhas',
            'timezone' => $tz,
            'status' => 'active',
            'confirmation_hours' => 24,
            'reactivation_months' => 6,
            'business_days' => [1, 2, 3, 4, 5, 6],
            'business_starts_at_hour' => 9,
            'business_ends_at_hour' => 17,
            'appointment_slot_minutes' => 30,
            'business_breaks' => [
                ['label' => 'Almoço', 'start_hour' => 12, 'start_minute' => 0, 'end_hour' => 13, 'end_minute' => 0],
            ],
            'hours_configured_at' => $openedAt->copy()->addDays(1),
            'guide_viewed_at' => $openedAt->copy()->addDays(1),
            'onboarding_completed_at' => $openedAt->copy()->addDays(3),
            'setup_wizard_completed_at' => $openedAt->copy()->addDays(3),
            'public_booking_token' => 'patinhas-demo-booking',
        ]);
        $company->forceFill(['created_at' => $openedAt, 'updated_at' => now()])->save(['timestamps' => false]);

        $this->purgeTransientData($company);

        CompanySubscription::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => $openedAt,
                'ends_at' => null,
                'created_at' => $openedAt,
                'updated_at' => now(),
            ],
        );

        $owner = User::updateOrCreate(
            ['email' => 'demo@clientloop.test'],
            [
                'company_id' => $company->id,
                'name' => 'Marina Costa',
                'email_verified_at' => $openedAt,
                'password' => Hash::make('clientloop123'),
                'role' => 'owner',
                'is_super_admin' => false,
                'created_at' => $openedAt,
            ],
        );
        $attendant = User::updateOrCreate(
            ['email' => 'atendente@clientloop.test'],
            [
                'company_id' => $company->id,
                'name' => 'Paula Atendente',
                'email_verified_at' => $openedAt->copy()->addMonths(10),
                'password' => Hash::make('clientloop123'),
                'role' => 'attendant',
                'is_super_admin' => false,
                'created_at' => $openedAt->copy()->addMonths(10),
            ],
        );
        User::updateOrCreate(
            ['email' => 'admin@clientloop.test'],
            [
                'company_id' => null,
                'name' => 'Administração ClientLoop',
                'email_verified_at' => $openedAt,
                'password' => Hash::make('clientloop123'),
                'role' => 'owner',
                'is_super_admin' => true,
            ],
        );

        $groomers = collect([
            'Marina' => Groomer::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'name' => 'Marina'],
                ['active' => true, 'created_at' => $openedAt],
            ),
            'Paula' => Groomer::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'name' => 'Paula'],
                ['active' => true, 'created_at' => $openedAt->copy()->addMonths(10)],
            ),
        ]);

        $services = collect([
            ['name' => 'Banho', 'suggested_price' => 45, 'duration_minutes' => 60, 'return_interval_months' => 1],
            ['name' => 'Tosa', 'suggested_price' => 80, 'duration_minutes' => 120],
            ['name' => 'Banho com higiênico e hidratação', 'suggested_price' => 55, 'duration_minutes' => 60],
        ])->mapWithKeys(fn (array $data) => [$data['name'] => Service::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $company->id, 'name' => $data['name']],
            [...$data, 'company_id' => $company->id, 'active' => true, 'created_at' => $openedAt],
        )]);

        $customerDefs = [
            ['name' => 'Ana Beatriz Lima', 'phone' => '5511998765432', 'joined' => 0, 'last_activity' => 1],
            ['name' => 'Carlos Eduardo Alves', 'phone' => '5511987654321', 'joined' => 3, 'last_activity' => 2],
            ['name' => 'Fernanda Souza', 'phone' => '5511976543210', 'joined' => 6, 'last_activity' => 5],
            ['name' => 'João Pedro Martins', 'phone' => '5511965432109', 'joined' => 2, 'last_activity' => 270],
            ['name' => 'Mariana Oliveira', 'phone' => '5511954321098', 'joined' => 12, 'last_activity' => 1],
            ['name' => 'Ricardo Mendes', 'phone' => '5511943210987', 'joined' => 15, 'last_activity' => 20],
        ];
        $responsibles = collect($customerDefs)->mapWithKeys(function (array $data) use ($company, $openedAt): array {
            $joinedAt = $openedAt->copy()->addMonths($data['joined']);

            return [$data['name'] => Customer::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'phone' => $data['phone']],
                [
                    'name' => $data['name'],
                    'last_activity_at' => now()->subDays($data['last_activity']),
                    'created_at' => $joinedAt,
                    'updated_at' => now(),
                ],
            )];
        });

        $petDefs = [
            ['name' => 'Thor', 'responsible' => 'Ana Beatriz Lima', 'species' => 'Cachorro', 'breed' => 'Shih-tzu', 'size' => 'small', 'temperament' => 'Calmo', 'coat' => 'Longa', 'allergies' => null, 'weight_kg' => 5.2, 'joined' => 0],
            ['name' => 'Mel', 'responsible' => 'Carlos Eduardo Alves', 'species' => 'Cachorro', 'breed' => 'Labrador', 'size' => 'large', 'temperament' => 'Agitado', 'coat' => 'Curta', 'allergies' => 'Shampoo com perfume', 'weight_kg' => 28.0, 'joined' => 3],
            ['name' => 'Nina', 'responsible' => 'Fernanda Souza', 'species' => 'Gato', 'breed' => 'Siamês', 'size' => 'small', 'temperament' => 'Arisco', 'coat' => 'Curta', 'allergies' => null, 'weight_kg' => 3.8, 'joined' => 6],
            ['name' => 'Bob', 'responsible' => 'João Pedro Martins', 'species' => 'Cachorro', 'breed' => 'Vira-lata', 'size' => 'medium', 'temperament' => 'Brincalhão', 'coat' => 'Média', 'allergies' => null, 'weight_kg' => 14.5, 'joined' => 2],
            ['name' => 'Amora', 'responsible' => 'Mariana Oliveira', 'species' => 'Cachorro', 'breed' => 'Poodle', 'size' => 'small', 'temperament' => 'Calmo', 'coat' => 'Cacheada', 'allergies' => null, 'weight_kg' => 6.1, 'joined' => 12],
            ['name' => 'Pingo', 'responsible' => 'Mariana Oliveira', 'species' => 'Gato', 'breed' => 'SRD', 'size' => 'small', 'temperament' => 'Calmo', 'coat' => 'Curta', 'allergies' => null, 'weight_kg' => 4.0, 'joined' => 15],
            ['name' => 'Luna', 'responsible' => 'Ricardo Mendes', 'species' => 'Cachorro', 'breed' => 'Golden Retriever', 'size' => 'large', 'temperament' => 'Calmo', 'coat' => 'Longa', 'allergies' => null, 'weight_kg' => 32.0, 'joined' => 15],
        ];
        $pets = collect($petDefs)->mapWithKeys(function (array $data) use ($company, $responsibles, $openedAt): array {
            $joinedAt = $openedAt->copy()->addMonths($data['joined']);

            return [$data['name'] => Pet::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'customer_id' => $responsibles[$data['responsible']]->id, 'name' => $data['name']],
                [
                    'species' => $data['species'],
                    'breed' => $data['breed'],
                    'size' => $data['size'],
                    'temperament' => $data['temperament'],
                    'coat' => $data['coat'],
                    'allergies' => $data['allergies'],
                    'weight_kg' => $data['weight_kg'],
                    'created_at' => $joinedAt,
                    'updated_at' => now(),
                ],
            )];
        });

        $offerDefinitions = [
            ['name' => '4 banhos', 'sequence' => ['Banho', 'Banho', 'Banho', 'Banho com higiênico e hidratação'], 'suggested_price' => 160],
            ['name' => '4 tosas', 'sequence' => ['Tosa', 'Tosa', 'Tosa', 'Tosa'], 'suggested_price' => 290],
        ];
        $offers = collect($offerDefinitions)->mapWithKeys(function (array $data) use ($company, $services, $openedAt): array {
            $offer = PackageOffer::withoutGlobalScopes()->updateOrCreate(['company_id' => $company->id, 'name' => $data['name']], [
                'credits' => count($data['sequence']),
                'suggested_price' => $data['suggested_price'],
                'active' => true,
                'created_at' => $openedAt->copy()->addMonths(1),
            ]);
            $offer->items()->delete();
            foreach ($data['sequence'] as $position => $serviceName) {
                $offer->items()->create(['company_id' => $company->id, 'service_id' => $services[$serviceName]->id, 'position' => $position + 1]);
            }

            return [$data['name'] => $offer->fresh()];
        });

        $templates = app(DefaultMessageTemplateService::class)->provision($company);

        $this->seedUsageHistory($company, $openedAt);
        $this->seedActivityTrail($company, $owner, $attendant, $openedAt);

        $exhaustedThor = $this->createPaidPackage($company, $pets['Thor'], $offers['4 banhos'], $openedAt->copy()->addMonths(4), $openedAt->copy()->addMonths(10));
        $this->seedExhaustedPackageVisits($company, $exhaustedThor, $pets['Thor'], $responsibles['Ana Beatriz Lima'], $services, $groomers, $openedAt->copy()->addMonths(4)->addWeek());

        $exhaustedThorTwo = $this->createPaidPackage($company, $pets['Thor'], $offers['4 banhos'], $openedAt->copy()->addMonths(11), $openedAt->copy()->addMonths(17));
        $this->seedExhaustedPackageVisits($company, $exhaustedThorTwo, $pets['Thor'], $responsibles['Ana Beatriz Lima'], $services, $groomers, $openedAt->copy()->addMonths(11)->addWeek());

        $exhaustedNina = $this->createPaidPackage($company, $pets['Nina'], $offers['4 banhos'], $openedAt->copy()->addMonths(8), $openedAt->copy()->addMonths(14));
        $this->seedExhaustedPackageVisits($company, $exhaustedNina, $pets['Nina'], $responsibles['Fernanda Souza'], $services, $groomers, $openedAt->copy()->addMonths(8)->addWeek());

        $historyUntil = $this->previousBusinessDay()->copy()->subDay();
        $this->seedVisitCadence($company, $pets, $responsibles, $services, $groomers, [
            ['pet' => 'Thor', 'service' => 'Banho', 'every_days' => 32, 'from' => $openedAt, 'until' => $historyUntil, 'hour' => 9, 'groomer' => 'Marina'],
            ['pet' => 'Mel', 'service' => 'Tosa', 'every_days' => 45, 'from' => $openedAt->copy()->addMonths(3), 'until' => $historyUntil, 'hour' => 10, 'groomer' => 'Paula'],
            ['pet' => 'Nina', 'service' => 'Banho', 'every_days' => 35, 'from' => $openedAt->copy()->addMonths(6), 'until' => $historyUntil, 'hour' => 11, 'groomer' => 'Marina'],
            ['pet' => 'Bob', 'service' => 'Banho', 'every_days' => 30, 'from' => $openedAt->copy()->addMonths(2), 'until' => now($tz)->subMonths(9), 'hour' => 15, 'groomer' => 'Paula'],
            ['pet' => 'Amora', 'service' => 'Banho', 'every_days' => 28, 'from' => $openedAt->copy()->addMonths(12), 'until' => $historyUntil, 'hour' => 14, 'groomer' => 'Marina'],
            ['pet' => 'Pingo', 'service' => 'Banho', 'every_days' => 40, 'from' => $openedAt->copy()->addMonths(15), 'until' => $historyUntil, 'hour' => 16, 'groomer' => 'Paula'],
            ['pet' => 'Luna', 'service' => 'Banho', 'every_days' => 30, 'from' => $openedAt->copy()->addMonths(15), 'until' => $historyUntil->copy()->subWeeks(2), 'hour' => 9, 'groomer' => 'Marina'],
        ]);

        $packages = [
            'thor' => $this->createPaidPackage($company, $pets['Thor'], $offers['4 banhos'], today()->subDays(10), today()->addMonths(6)),
            'mel' => $this->createPackage($company, $pets['Mel'], $offers['4 tosas'], 'pending', today(), null),
            'amora' => $this->createPaidPackage($company, $pets['Amora'], $offers['4 banhos'], today()->subDays(3), today()->addMonths(6)),
            'luna_low' => $this->createPaidPackage($company, $pets['Luna'], $offers['4 banhos'], today()->subMonths(2), today()->addMonths(4)),
            'pingo_low' => $this->createPaidPackage($company, $pets['Pingo'], $offers['4 banhos'], today()->subMonths(1), today()->addMonths(5)),
        ];

        // 1 crédito restante → cards "pouco saldo" e "próximas etapas".
        $this->redeemPackageItems($company, $packages['luna_low'], $pets['Luna'], $responsibles['Ricardo Mendes'], $groomers, today()->subMonths(2)->addWeek(), keepLast: 1);
        $this->redeemPackageItems($company, $packages['pingo_low'], $pets['Pingo'], $responsibles['Mariana Oliveira'], $groomers, today()->subWeeks(3), keepLast: 1);

        $today = now('America/Sao_Paulo')->startOfDay();
        $nextBusinessDay = $this->nextBusinessDay();
        $followingBusinessDay = $this->nextBusinessDay($nextBusinessDay);
        $lastBusinessDay = $this->previousBusinessDay();
        $ninaRecurrenceDay = $followingBusinessDay->copy()->addWeek()->startOfDay();
        while ($ninaRecurrenceDay->isSunday()) {
            $ninaRecurrenceDay->addDay();
        }
        $recurrenceGroup = 'demo-nina-recurrence';

        $appointments = collect([
            // Agenda de hoje (card do overview).
            ['key' => 'luna-hoje', 'pet' => 'Luna', 'service' => 'Banho', 'scheduled_at' => $today->copy()->setTime(9, 0), 'status' => 'confirmed', 'package' => 'luna_low', 'groomer' => 'Marina'],
            ['key' => 'pingo-hoje', 'pet' => 'Pingo', 'service' => 'Banho', 'scheduled_at' => $today->copy()->setTime(10, 0), 'status' => 'scheduled', 'groomer' => 'Paula'],
            ['key' => 'nina-hoje', 'pet' => 'Nina', 'service' => 'Banho', 'scheduled_at' => $today->copy()->setTime(14, 0), 'status' => 'confirmed', 'groomer' => 'Marina'],
            // Semana operacional.
            ['key' => 'thor-agendado', 'pet' => 'Thor', 'service' => 'Banho', 'scheduled_at' => $nextBusinessDay->copy()->setTime(9, 0), 'status' => 'scheduled', 'package' => 'thor', 'groomer' => 'Marina'],
            ['key' => 'mel-confirmado', 'pet' => 'Mel', 'service' => 'Tosa', 'scheduled_at' => $nextBusinessDay->copy()->setTime(9, 0), 'status' => 'confirmed', 'groomer' => 'Paula'],
            ['key' => 'amora-agendado', 'pet' => 'Amora', 'service' => 'Banho', 'scheduled_at' => $nextBusinessDay->copy()->setTime(14, 0), 'status' => 'scheduled', 'package' => 'amora', 'groomer' => 'Marina'],
            ['key' => 'pingo-paralelo', 'pet' => 'Pingo', 'service' => 'Banho', 'scheduled_at' => $nextBusinessDay->copy()->setTime(14, 0), 'status' => 'scheduled', 'groomer' => 'Paula'],
            ['key' => 'nina-confirmado', 'pet' => 'Nina', 'service' => 'Banho com higiênico e hidratação', 'scheduled_at' => $followingBusinessDay->copy()->setTime(10, 0), 'status' => 'confirmed', 'groomer' => 'Marina', 'recurrence_group' => $recurrenceGroup],
            ['key' => 'nina-semana-seguinte', 'pet' => 'Nina', 'service' => 'Banho com higiênico e hidratação', 'scheduled_at' => $ninaRecurrenceDay->copy()->setTime(10, 0), 'status' => 'scheduled', 'groomer' => 'Marina', 'recurrence_group' => $recurrenceGroup],
            ['key' => 'thor-concluido', 'pet' => 'Thor', 'service' => 'Banho', 'scheduled_at' => $lastBusinessDay->copy()->setTime(9, 0), 'status' => 'completed', 'package' => 'thor', 'groomer' => 'Marina'],
            ['key' => 'bob-falta', 'pet' => 'Bob', 'service' => 'Banho', 'scheduled_at' => $lastBusinessDay->copy()->setTime(15, 0), 'status' => 'no_show', 'groomer' => 'Paula'],
            ['key' => 'luna-cancelado', 'pet' => 'Luna', 'service' => 'Banho', 'scheduled_at' => $lastBusinessDay->copy()->subWeek()->setTime(11, 0), 'status' => 'cancelled', 'groomer' => 'Marina'],
        ])->mapWithKeys(function (array $data) use ($company, $pets, $services, $packages, $groomers): array {
            $pet = $pets[$data['pet']];
            $service = $services[$data['service']];

            return [$data['key'] => $this->forceAppointment([
                'company_id' => $company->id,
                'customer_id' => $pet->customer_id,
                'pet_id' => $pet->id,
                'service_id' => $service->id,
                'pet_package_id' => isset($data['package']) ? $packages[$data['package']]->id : null,
                'groomer_id' => $groomers[$data['groomer']]->id,
                'scheduled_at' => $data['scheduled_at'],
                'duration_minutes' => $service->duration_minutes,
                'status' => $data['status'],
                'recurrence_group' => $data['recurrence_group'] ?? null,
            ])];
        });

        PackageRedemption::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'pet_package_id' => $packages['thor']->id,
            'pet_package_item_id' => $packages['thor']->items()->orderBy('position')->first()?->id,
            'appointment_id' => $appointments['thor-concluido']->id,
            'redeemed_at' => $lastBusinessDay->copy()->setTime(10, 0),
        ]);

        $pastCampaign = Campaign::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Retornos de inverno',
            'type' => 'recall',
            'status' => 'completed',
            'message_template_id' => $templates['Hora de voltar']->id,
            'filters' => ['return_months' => 3],
            'starts_at' => $openedAt->copy()->addMonths(12),
            'created_at' => $openedAt->copy()->addMonths(12)->subDays(3),
        ]);
        foreach (['Ana Beatriz Lima', 'Carlos Eduardo Alves', 'Fernanda Souza'] as $name) {
            CampaignRecipient::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'campaign_id' => $pastCampaign->id,
                'customer_id' => $responsibles[$name]->id,
                'status' => 'queued',
            ]);
        }

        $campaign = Campaign::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Pets para reativar',
            'type' => 'reactivation',
            'status' => 'draft',
            'message_template_id' => $templates['Reativação']->id,
            'filters' => [],
            'starts_at' => null,
        ]);
        Campaign::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Retornos da semana',
            'type' => 'recall',
            'status' => 'draft',
            'message_template_id' => $templates['Hora de voltar']->id,
            'filters' => ['return_months' => 3],
            'starts_at' => now()->addDay()->startOfHour(),
        ]);

        $this->seedCompletedContactTasks($company, $templates, $responsibles);

        ContactTask::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'appointment_id' => $appointments['thor-agendado']->id,
            'customer_id' => $responsibles['Ana Beatriz Lima']->id,
            'message_template_id' => $templates['Confirmação de banho']->id,
            'type' => 'confirmation',
            'priority' => 'high',
            'due_at' => now(),
            'cycle_key' => 'appointment:'.$appointments['thor-agendado']->id,
            'rendered_message' => 'Olá, Ana Beatriz Lima! O banho de Thor está marcado para '.$nextBusinessDay->format('d/m').' às 09:00. Podemos confirmar? '.$company->publicBookingUrl($responsibles['Ana Beatriz Lima']->id),
            'status' => 'pending',
        ]);
        ContactTask::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'appointment_id' => $appointments['amora-agendado']->id,
            'customer_id' => $responsibles['Mariana Oliveira']->id,
            'message_template_id' => $templates['Confirmação de banho']->id,
            'type' => 'confirmation',
            'priority' => 'normal',
            'due_at' => now()->addHour(),
            'cycle_key' => 'appointment:'.$appointments['amora-agendado']->id,
            'rendered_message' => 'Olá, Mariana Oliveira! O banho de Amora está marcado para '.$nextBusinessDay->format('d/m').' às 14:00. Podemos confirmar?',
            'status' => 'pending',
        ]);
        ContactTask::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $responsibles['João Pedro Martins']->id,
            'campaign_id' => $campaign->id,
            'message_template_id' => $templates['Reativação']->id,
            'type' => 'reactivation',
            'priority' => 'normal',
            'due_at' => now()->subHour(),
            'cycle_key' => 'reactivation:'.now()->format('Y-m'),
            'rendered_message' => 'Olá, João Pedro Martins! Faz um tempo que não vemos Bob. Quer reservar um horário?',
            'status' => 'pending',
        ]);
        ContactTask::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'appointment_id' => $appointments['bob-falta']->id,
            'customer_id' => $responsibles['João Pedro Martins']->id,
            'message_template_id' => $templates['Hora de voltar']->id,
            'type' => 'recall',
            'priority' => 'high',
            'due_at' => now()->subMinutes(30),
            'cycle_key' => 'followup:no_show:'.$appointments['bob-falta']->id,
            'rendered_message' => 'Olá, João Pedro Martins! Sentimos falta de Bob ontem. Quer remarcar o banho?',
            'status' => 'pending',
        ]);
        ContactTask::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $responsibles['Ricardo Mendes']->id,
            'message_template_id' => $templates['Hora de voltar']->id,
            'type' => 'recall',
            'priority' => 'normal',
            'due_at' => now()->subDays(2),
            'cycle_key' => 'recall:luna:'.now()->format('Y-m'),
            'rendered_message' => 'Olá, Ricardo Mendes! Sentimos falta de Luna na Pet Shop Patinhas. Quer agendar um banho?',
            'status' => 'pending',
        ]);

        WaitlistEntry::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $responsibles['Fernanda Souza']->id,
            'pet_id' => $pets['Nina']->id,
            'service_id' => $services['Banho']->id,
            'preferred_date' => $nextBusinessDay->toDateString(),
            'preferred_time' => '15:00',
            'status' => 'waiting',
            'notes' => 'Prefere tarde, se surgir encaixe.',
        ]);

        $responsibles['Ana Beatriz Lima']->update(['next_return_at' => null, 'last_activity_at' => $lastBusinessDay]);
        $responsibles['João Pedro Martins']->update(['last_activity_at' => now()->subMonths(9)]);
        $responsibles['Ricardo Mendes']->update(['next_return_at' => now()->subDays(5)]);
    }

    private function purgeTransientData(Company $company): void
    {
        PackageRedemption::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        ContactTask::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Appointment::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        WaitlistEntry::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        CampaignRecipient::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Campaign::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        PetPackageItem::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        PetPackage::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        ActivityLog::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        UsageRecord::withoutGlobalScopes()->where('company_id', $company->id)->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function forceAppointment(array $attributes): Appointment
    {
        $scheduledAt = Carbon::parse($attributes['scheduled_at']);
        $duration = (int) ($attributes['duration_minutes'] ?? 60);

        return Appointment::withoutEvents(fn (): Appointment => Appointment::withoutGlobalScopes()->create([
            ...$attributes,
            'ends_at' => $scheduledAt->copy()->addMinutes($duration),
            'confirmation_token' => Str::random(40),
            'created_at' => $scheduledAt->copy()->subDays(2),
            'updated_at' => $scheduledAt,
        ]));
    }

    private function createPaidPackage(Company $company, Pet $pet, PackageOffer $offer, Carbon $purchasedAt, ?Carbon $validUntil): PetPackage
    {
        return $this->createPackage($company, $pet, $offer, 'paid', $purchasedAt, $validUntil);
    }

    private function createPackage(Company $company, Pet $pet, PackageOffer $offer, string $paymentStatus, Carbon $purchasedAt, ?Carbon $validUntil): PetPackage
    {
        $package = PetPackage::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'pet_id' => $pet->id,
            'package_offer_id' => $offer->id,
            'name' => $offer->name,
            'total_credits' => $offer->credits,
            'price' => $offer->suggested_price,
            'payment_status' => $paymentStatus,
            'purchased_at' => $purchasedAt,
            'valid_until' => $validUntil,
            'created_at' => $purchasedAt,
        ]);

        $this->syncPackageItems($package, $offer);

        return $package->fresh(['items']);
    }

    private function syncPackageItems(PetPackage $package, PackageOffer $offer): void
    {
        if ($package->items()->exists()) {
            return;
        }

        $offerItems = $offer->items()->with('service')->orderBy('position')->get();
        foreach ($offerItems as $item) {
            $package->items()->create([
                'company_id' => $package->company_id,
                'service_id' => $item->service_id,
                'service_name' => $item->service->name,
                'duration_minutes' => $item->service->duration_minutes,
                'position' => $item->position,
            ]);
        }
    }

    /**
     * @param  Collection<string, Groomer>  $groomers
     */
    private function redeemPackageItems(
        Company $company,
        PetPackage $package,
        Pet $pet,
        Customer $customer,
        $groomers,
        Carbon $firstVisit,
        int $keepLast = 0,
    ): void {
        $package->load('items.service');
        $items = $package->items->values();
        $redeemCount = max(0, $items->count() - $keepLast);
        $cursor = $this->snapToBusinessDay($firstVisit->copy());

        for ($index = 0; $index < $redeemCount; $index++) {
            $item = $items[$index];
            $service = $item->service;
            $hour = 9 + (($index % 2) * 2);
            $scheduledAt = $cursor->copy()->setTime($hour, 0);
            $appointment = $this->forceAppointment([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'pet_id' => $pet->id,
                'service_id' => $service->id,
                'pet_package_id' => $package->id,
                'groomer_id' => $groomers['Marina']->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $service->duration_minutes,
                'status' => 'completed',
            ]);
            PackageRedemption::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'pet_package_id' => $package->id,
                'pet_package_item_id' => $item->id,
                'appointment_id' => $appointment->id,
                'redeemed_at' => $scheduledAt->copy()->addHour(),
            ]);
            $cursor = $this->snapToBusinessDay($cursor->copy()->addDays(21));
        }
    }

    /**
     * @param  Collection<string, Service>  $services
     * @param  Collection<string, Groomer>  $groomers
     */
    private function seedExhaustedPackageVisits(
        Company $company,
        PetPackage $package,
        Pet $pet,
        Customer $customer,
        $services,
        $groomers,
        Carbon $firstVisit
    ): void {
        $this->redeemPackageItems($company, $package, $pet, $customer, $groomers, $firstVisit, keepLast: 0);
    }

    /**
     * @param  Collection<string, Pet>  $pets
     * @param  Collection<string, Customer>  $responsibles
     * @param  Collection<string, Service>  $services
     * @param  Collection<string, Groomer>  $groomers
     * @param  list<array<string, mixed>>  $cadences
     */
    private function seedVisitCadence(Company $company, $pets, $responsibles, $services, $groomers, array $cadences): void
    {
        foreach ($cadences as $cadence) {
            $cursor = $this->snapToBusinessDay(Carbon::parse($cadence['from'])->startOfDay());
            $until = Carbon::parse($cadence['until'])->endOfDay();
            $visit = 0;

            while ($cursor->lte($until)) {
                $status = match (true) {
                    $visit > 0 && $visit % 11 === 0 => 'no_show',
                    $visit > 0 && $visit % 17 === 0 => 'cancelled',
                    default => 'completed',
                };
                $serviceName = $cadence['service'];
                if ($serviceName === 'Banho' && $visit > 0 && $visit % 4 === 0 && isset($services['Banho com higiênico e hidratação'])) {
                    $serviceName = 'Banho com higiênico e hidratação';
                }
                $service = $services[$serviceName];
                $paulaJoinedAt = now('America/Sao_Paulo')->subMonths(8)->startOfDay();
                $groomerName = $cursor->lt($paulaJoinedAt)
                    ? 'Marina'
                    : ($visit % 2 === 0 ? 'Marina' : 'Paula');

                $this->forceAppointment([
                    'company_id' => $company->id,
                    'customer_id' => $pets[$cadence['pet']]->customer_id,
                    'pet_id' => $pets[$cadence['pet']]->id,
                    'service_id' => $service->id,
                    'groomer_id' => $groomers[$groomerName]->id,
                    'scheduled_at' => $cursor->copy()->setTime((int) $cadence['hour'], 0),
                    'duration_minutes' => $service->duration_minutes,
                    'status' => $status,
                ]);

                $cursor = $this->snapToBusinessDay($cursor->copy()->addDays((int) $cadence['every_days']));
                $visit++;
            }
        }
    }

    private function seedUsageHistory(Company $company, Carbon $openedAt): void
    {
        $cursor = $openedAt->copy()->startOfMonth();
        $monthIndex = 0;
        while ($cursor->lte(now()->startOfMonth())) {
            UsageRecord::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'period' => $cursor->format('Y-m'),
                'contacts_count' => 4 + min(20, $monthIndex + 2),
                'tasks_count' => 12 + ($monthIndex * 3),
                'created_at' => $cursor->copy()->endOfMonth()->min(now()),
            ]);
            $cursor->addMonth();
            $monthIndex++;
        }
    }

    private function seedActivityTrail(Company $company, User $owner, User $attendant, Carbon $openedAt): void
    {
        $events = [
            [$openedAt->copy()->addDays(1), $owner, 'company.hours_configured', null],
            [$openedAt->copy()->addDays(3), $owner, 'company.onboarding_completed', null],
            [$openedAt->copy()->addMonths(4), $owner, 'package.sold', null],
            [$openedAt->copy()->addMonths(10), $owner, 'team.member_added', null],
            [$openedAt->copy()->addMonths(12), $attendant, 'campaign.completed', null],
            [now()->subDays(2), $attendant, 'appointment.completed', null],
            [now()->subDay(), $owner, 'appointment.no_show', null],
        ];

        foreach ($events as [$at, $user, $event]) {
            ActivityLog::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'event' => $event,
                'subject_type' => Company::class,
                'subject_id' => $company->id,
                'properties' => ['source' => 'demo'],
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        }
    }

    /**
     * @param  Collection<string, MessageTemplate>  $templates
     * @param  Collection<string, Customer>  $responsibles
     */
    private function seedCompletedContactTasks(Company $company, $templates, $responsibles): void
    {
        $samples = [
            ['customer' => 'Ana Beatriz Lima', 'type' => 'confirmation', 'template' => 'Confirmação de banho', 'outcome' => 'confirmed', 'days_ago' => 20],
            ['customer' => 'Carlos Eduardo Alves', 'type' => 'confirmation', 'template' => 'Confirmação de banho', 'outcome' => 'confirmed', 'days_ago' => 45],
            ['customer' => 'Fernanda Souza', 'type' => 'recall', 'template' => 'Hora de voltar', 'outcome' => 'scheduled', 'days_ago' => 60],
            ['customer' => 'Mariana Oliveira', 'type' => 'confirmation', 'template' => 'Confirmação de banho', 'outcome' => 'no_response', 'days_ago' => 10],
            ['customer' => 'João Pedro Martins', 'type' => 'reactivation', 'template' => 'Reativação', 'outcome' => 'no_response', 'days_ago' => 100],
        ];

        foreach ($samples as $sample) {
            $dueAt = now()->subDays($sample['days_ago']);
            ContactTask::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'customer_id' => $responsibles[$sample['customer']]->id,
                'message_template_id' => $templates[$sample['template']]->id,
                'type' => $sample['type'],
                'priority' => 'normal',
                'due_at' => $dueAt,
                'cycle_key' => 'history:'.$sample['type'].':'.$sample['customer'].':'.$sample['days_ago'],
                'rendered_message' => 'Mensagem histórica de '.$sample['type'].' para '.$sample['customer'].'.',
                'status' => 'completed',
                'outcome' => $sample['outcome'],
                'completed_at' => $dueAt->copy()->addHours(2),
                'created_at' => $dueAt,
            ]);
        }
    }

    private function snapToBusinessDay(Carbon $date): Carbon
    {
        $date = $date->copy()->startOfDay();
        while ($date->isSunday()) {
            $date->addDay();
        }

        return $date;
    }

    private function nextBusinessDay(?Carbon $after = null): Carbon
    {
        $date = ($after ?? now('America/Sao_Paulo'))->copy()->startOfDay();

        do {
            $date->addDay();
        } while ($date->isSunday());

        return $date;
    }

    private function previousBusinessDay(): Carbon
    {
        $date = now('America/Sao_Paulo')->copy()->startOfDay();

        do {
            $date->subDay();
        } while ($date->isSunday());

        return $date;
    }
}
