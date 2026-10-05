<?php

namespace Database\Seeders;

use App\Enums\CashEntryDirection;
use App\Enums\CashExpenseCategory;
use App\Enums\CompanyVertical;
use App\Enums\ReceiptPaymentMethod;
use App\Enums\ReceiptStatus;
use App\Enums\ServiceOrderStatus;
use App\Filament\Resources\ServiceOrders\ServiceOrderResource;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CashEntry;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\Groomer;
use App\Models\Plan;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceReceipt;
use App\Models\UsageRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DefaultMessageTemplateService;
use App\Services\ServiceReceiptService;
use App\Services\TemplateRenderer;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoWorkshopSeeder extends Seeder
{
    public function run(): void
    {
        $openedAt = now('America/Sao_Paulo')->subMonths(18)->startOfDay();
        $plan = Plan::firstOrCreate(['name' => 'Plano demonstração'], ['contact_limit' => 500, 'task_limit' => 1000, 'is_default' => false]);
        $company = Company::updateOrCreate(['slug' => 'oficina-centro-demo'], [
            'name' => 'Oficina Centro',
            'vertical' => CompanyVertical::Automotive,
            'timezone' => 'America/Sao_Paulo',
            'status' => 'active',
            'business_days' => [1, 2, 3, 4, 5],
            'business_starts_at_hour' => 8,
            'business_ends_at_hour' => 18,
            'appointment_slot_minutes' => 60,
            'confirmation_hours' => 24,
            'reactivation_months' => 6,
            'public_booking_token' => 'oficina-centro-demo',
        ]);
        $company->forceFill(['created_at' => $openedAt, 'updated_at' => now()])->save(['timestamps' => false]);

        $owner = User::updateOrCreate(
            ['email' => 'oficina@clientloop.test'],
            [
                'company_id' => $company->id,
                'name' => 'Roberto Nunes',
                'email_verified_at' => $openedAt,
                'password' => Hash::make('password'),
                'role' => 'owner',
                'is_super_admin' => false,
                'created_at' => $openedAt,
            ],
        );

        $this->purge($company, $owner);

        $subscription = CompanySubscription::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $company->id],
            ['plan_id' => $plan->id, 'status' => 'active', 'starts_at' => $openedAt, 'ends_at' => null],
        );
        $this->stamp($subscription, $openedAt);
        $this->seedUsageHistory($company, $openedAt);

        $services = $this->services($company, $openedAt);
        $ana = $this->customer($company, 'Ana Lima', '5511981000001', $openedAt, now());
        $bruno = $this->customer($company, 'Bruno Souza', '5511981000002', $openedAt->copy()->addMonths(3), now());
        $carla = $this->customer($company, 'Carla Dias', '5511981000003', $openedAt->copy()->addMonths(6), now());
        $diego = $this->customer($company, 'Diego Ramos', '5511981000004', $openedAt->copy()->addMonths(9), now()->subDay());
        $helena = $this->customer($company, 'Helena Cruz', '5511981000005', $openedAt->copy()->addMonths(12), now()->subDays(2));
        $paulo = $this->customer($company, 'Paulo Reis', '5511981000006', $openedAt->copy()->addMonths(15), now()->startOfMonth());

        $gol = $this->vehicle($company, $ana, 'ABC1D23', 'Volkswagen', 'Gol', $openedAt);
        $onix = $this->vehicle($company, $bruno, 'DEF2E34', 'Chevrolet', 'Onix', $openedAt->copy()->addMonths(3));
        $hb20 = $this->vehicle($company, $carla, 'GHI3F45', 'Hyundai', 'HB20', $openedAt->copy()->addMonths(6));
        $uno = $this->vehicle($company, $diego, 'JKL4G56', 'Fiat', 'Uno', $openedAt->copy()->addMonths(9));
        $civic = $this->vehicle($company, $helena, 'MNO5H67', 'Honda', 'Civic', $openedAt->copy()->addMonths(12));
        $corolla = $this->vehicle($company, $paulo, 'PQR7J89', 'Toyota', 'Corolla', $openedAt->copy()->addMonths(15));
        $this->order($company, $ana, $gol, ServiceOrderStatus::Open, now(), $services['Troca de óleo'], '120.00', 'Cliente deixou o carro de manhã.');
        $this->order($company, $bruno, $onix, ServiceOrderStatus::InProgress, now(), $services['Pastilhas'], '180.00', 'Pastilhas dianteiras em troca.');
        $readyToday = $this->order($company, $carla, $hb20, ServiceOrderStatus::Ready, now(), $services['Alinhamento'], '150.00', 'Alinhamento concluído hoje.');
        $readyWaiting = $this->order($company, $diego, $uno, ServiceOrderStatus::Ready, now()->subDay(), $services['Higienização'], '90.00', 'Pronto desde ontem.');
        $deliveredUnpaid = $this->order($company, $helena, $civic, ServiceOrderStatus::Delivered, now()->subDays(2), $services['Revisão'], '420.00', 'Entregue e ainda a prazo.');
        $deliveredPaid = $this->order($company, $paulo, $corolla, ServiceOrderStatus::Delivered, now()->startOfMonth(), $services['Freios'], '650.00', 'Freios pagos no mês.');

        app(ServiceReceiptService::class)->issue($readyToday, '150.00', ReceiptPaymentMethod::Pix, ReceiptStatus::Pending, now());
        app(ServiceReceiptService::class)->issue($deliveredUnpaid, '420.00', ReceiptPaymentMethod::OnAccount, ReceiptStatus::Pending, now()->subDays(2));
        app(ServiceReceiptService::class)->issue($deliveredPaid, '650.00', ReceiptPaymentMethod::Cash, ReceiptStatus::Paid, now()->startOfMonth());

        CashEntry::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'direction' => CashEntryDirection::Expense,
            'category' => CashExpenseCategory::Rent,
            'amount' => 400,
            'occurred_on' => now()->startOfMonth()->toDateString(),
            'notes' => 'Aluguel do box',
        ]);

        $readyWaiting->forceFill([
            'status_changed_at' => now()->subDay(),
            'ready_alerted_at' => now()->subDay()->setTime(8, 0),
        ])->saveQuietly();
        $deliveredUnpaid->forceFill([
            'status_changed_at' => now()->subDays(2),
            'unpaid_delivery_alerted_at' => now()->subDay()->setTime(8, 5),
        ])->saveQuietly();

        $this->notify($owner, $readyWaiting, 'Veículo pronto parado', 'JKL4G56 · Fiat Uno de Diego Ramos continua pronto para retirada.', now()->subDay()->setTime(8, 0));
        $this->notify($owner, $deliveredUnpaid, 'Entregue e ainda a receber', 'MNO5H67 · Honda Civic de Helena Cruz foi entregue e o recibo continua pendente.', now()->subDay()->setTime(8, 5));

        $mechanic = $this->mechanic($company, 'Roberto', $openedAt);
        $lucas = $this->mechanic($company, 'Lucas', $openedAt->copy()->addMonths(8));
        $this->seedSchedule($company, $services, $ana, $gol, $bruno, $onix, $mechanic);
        $this->seedHistory($company, $owner, $services, $mechanic, $lucas, [
            ['customer' => $ana, 'vehicle' => $gol, 'joined' => $openedAt],
            ['customer' => $bruno, 'vehicle' => $onix, 'joined' => $openedAt->copy()->addMonths(3)],
            ['customer' => $carla, 'vehicle' => $hb20, 'joined' => $openedAt->copy()->addMonths(6)],
            ['customer' => $diego, 'vehicle' => $uno, 'joined' => $openedAt->copy()->addMonths(9)],
            ['customer' => $helena, 'vehicle' => $civic, 'joined' => $openedAt->copy()->addMonths(12)],
            ['customer' => $paulo, 'vehicle' => $corolla, 'joined' => $openedAt->copy()->addMonths(15)],
        ]);
    }

    private function purge(Company $company, User $owner): void
    {
        DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $owner->id)
            ->delete();
        CampaignRecipient::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Campaign::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        ActivityLog::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        ContactTask::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Appointment::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Groomer::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        CashEntry::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        ServiceReceipt::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        ServiceOrderItem::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        ServiceOrder::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Vehicle::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Customer::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        Service::withoutGlobalScopes()->where('company_id', $company->id)->delete();
        UsageRecord::withoutGlobalScopes()->where('company_id', $company->id)->delete();
    }

    /** @return array<string, Service> */
    private function services(Company $company, Carbon $openedAt): array
    {
        $services = [];

        foreach ([
            'Troca de óleo' => '120.00',
            'Pastilhas' => '180.00',
            'Alinhamento' => '150.00',
            'Higienização' => '90.00',
            'Revisão' => '420.00',
            'Freios' => '650.00',
        ] as $name => $price) {
            $services[$name] = Service::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'name' => $name,
                'suggested_price' => $price,
                'duration_minutes' => 60,
                'return_interval_months' => $name === 'Troca de óleo' ? 6 : null,
                'active' => true,
            ]);
            $this->stamp($services[$name], $openedAt);
        }

        return $services;
    }

    /**
     * @param  array<string, Service>  $services
     */
    private function seedSchedule(Company $company, array $services, Customer $ana, Vehicle $gol, Customer $bruno, Vehicle $onix, Groomer $mechanic): void
    {
        $templates = app(DefaultMessageTemplateService::class)->provision($company);
        $today = now()->startOfDay()->setTime(10, 0);
        $tomorrow = now()->addDay()->startOfDay()->setTime(11, 0);
        $todayAppointment = Appointment::withoutEvents(fn (): Appointment => Appointment::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $ana->id,
            'vehicle_id' => $gol->id,
            'service_id' => $services['Troca de óleo']->id,
            'groomer_id' => $mechanic->id,
            'scheduled_at' => $today,
            'duration_minutes' => 60,
            'ends_at' => $today->copy()->addHour(),
            'status' => 'scheduled',
            'confirmation_token' => 'oficina-demo-hoje',
        ]));
        Appointment::withoutEvents(fn (): Appointment => Appointment::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $bruno->id,
            'vehicle_id' => $onix->id,
            'service_id' => $services['Pastilhas']->id,
            'groomer_id' => $mechanic->id,
            'scheduled_at' => $tomorrow,
            'duration_minutes' => 60,
            'ends_at' => $tomorrow->copy()->addHour(),
            'status' => 'confirmed',
            'confirmation_token' => 'oficina-demo-amanha',
        ]));

        ContactTask::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $ana->id,
            'appointment_id' => $todayAppointment->id,
            'message_template_id' => $templates['Confirmação de horário']->id,
            'type' => 'confirmation',
            'priority' => 'high',
            'due_at' => $today->copy()->subHours(24),
            'cycle_key' => 'appointment:'.$todayAppointment->id,
            'rendered_message' => app(TemplateRenderer::class)->render(
                $templates['Confirmação de horário']->body,
                $ana,
                $services['Troca de óleo'],
                $today,
                null,
                $company,
                route('appointment.confirm.show', 'oficina-demo-hoje'),
                $gol,
            ),
            'status' => 'pending',
        ]);
    }

    private function customer(Company $company, string $name, string $phone, Carbon $joinedAt, Carbon $lastActivityAt): Customer
    {
        $customer = Customer::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => $name,
            'phone' => $phone,
            'last_activity_at' => $lastActivityAt,
        ]);
        $this->stamp($customer, $joinedAt);

        return $customer;
    }

    private function vehicle(Company $company, Customer $customer, string $plate, string $brand, string $model, Carbon $joinedAt): Vehicle
    {
        $vehicle = Vehicle::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'plate' => $plate,
            'brand' => $brand,
            'model' => $model,
        ]);
        $this->stamp($vehicle, $joinedAt);

        return $vehicle;
    }

    private function mechanic(Company $company, string $name, Carbon $joinedAt): Groomer
    {
        $mechanic = Groomer::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => $name,
            'active' => true,
        ]);
        $this->stamp($mechanic, $joinedAt);

        return $mechanic;
    }

    private function order(Company $company, Customer $customer, Vehicle $vehicle, ServiceOrderStatus $status, Carbon $when, Service $service, string $price, string $notes): ServiceOrder
    {
        $order = ServiceOrder::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'opened_on' => $when->toDateString(),
            'status' => $status,
            'notes' => $notes,
        ]);
        $order->items()->create([
            'company_id' => $company->id,
            'service_id' => $service->id,
            'description' => $service->name,
            'quantity' => 1,
            'unit_price' => $price,
        ]);
        $order->forceFill([
            'status_changed_at' => $when,
            'created_at' => $when,
            'updated_at' => $when,
        ])->save(['timestamps' => false]);

        return $order;
    }

    /**
     * @param  array<string, Service>  $services
     * @param  list<array{customer: Customer, vehicle: Vehicle, joined: Carbon}>  $fleet
     */
    private function seedHistory(Company $company, User $owner, array $services, Groomer $mechanic, Groomer $lucas, array $fleet): void
    {
        $templates = app(DefaultMessageTemplateService::class)->provision($company);
        $historyEnds = now()->startOfMonth()->subDay()->startOfDay();
        $serviceNames = ['Troca de óleo', 'Alinhamento', 'Higienização', 'Revisão'];
        $hours = [9, 10, 11, 14, 15, 16];

        foreach ($fleet as $entry) {
            $cursor = $this->weekday($entry['joined']->copy()->addWeeks(2));
            $visit = 0;

            while ($cursor->lte($historyEnds)) {
                $cursor = $this->weekday($cursor);
                if ($cursor->gt($historyEnds)) {
                    break;
                }

                $service = $services[$serviceNames[$visit % count($serviceNames)]];
                $when = $cursor->copy()->setTime($hours[$visit % count($hours)], 0);
                $groomerId = $when->lt($lucas->created_at) || $visit % 2 === 0 ? $mechanic->id : $lucas->id;
                $order = $this->order(
                    $company,
                    $entry['customer'],
                    $entry['vehicle'],
                    ServiceOrderStatus::Delivered,
                    $when,
                    $service,
                    number_format((float) $service->suggested_price, 2, '.', ''),
                    'Serviço feito em '.$when->format('m/Y').'.',
                );
                app(ServiceReceiptService::class)->issue(
                    $order,
                    number_format((float) $service->suggested_price, 2, '.', ''),
                    ReceiptPaymentMethod::Pix,
                    ReceiptStatus::Paid,
                    $when,
                );
                $appointment = Appointment::withoutEvents(fn (): Appointment => Appointment::withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'customer_id' => $entry['customer']->id,
                    'vehicle_id' => $entry['vehicle']->id,
                    'service_id' => $service->id,
                    'groomer_id' => $groomerId,
                    'scheduled_at' => $when,
                    'duration_minutes' => 60,
                    'ends_at' => $when->copy()->addHour(),
                    'status' => 'completed',
                    'confirmation_token' => (string) Str::uuid(),
                ]));
                $this->stamp($appointment, $when->copy()->subDay());

                $cursor = $this->weekday($cursor->copy()->addMonths(4));
                $visit++;
            }
        }

        $rentMonth = $fleet[0]['joined']->copy()->startOfMonth();
        while ($rentMonth->lt(now()->startOfMonth())) {
            CashEntry::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'direction' => CashEntryDirection::Expense,
                'category' => CashExpenseCategory::Rent,
                'amount' => 400,
                'occurred_on' => $rentMonth->toDateString(),
                'notes' => 'Aluguel do box',
            ]);
            $rentMonth->addMonth();
        }

        $campaign = Campaign::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Revisão semestral',
            'type' => 'recall',
            'status' => 'completed',
            'message_template_id' => $templates['Hora de voltar']->id,
            'filters' => ['return_months' => 6],
            'starts_at' => $fleet[0]['joined']->copy()->addMonths(12),
        ]);
        $this->stamp($campaign, $fleet[0]['joined']->copy()->addMonths(12));
        foreach (array_slice($fleet, 0, 3) as $entry) {
            CampaignRecipient::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'campaign_id' => $campaign->id,
                'customer_id' => $entry['customer']->id,
                'status' => 'queued',
            ]);
        }

        foreach ([
            [2, 'confirmation', 'Confirmação de horário', 'confirmed'],
            [8, 'recall', 'Hora de voltar', 'scheduled'],
            [14, 'reactivation', 'Reativação', 'no_response'],
        ] as [$months, $type, $template, $outcome]) {
            $dueAt = $fleet[0]['joined']->copy()->addMonths($months)->setTime(9, 0);
            $task = ContactTask::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'customer_id' => $fleet[0]['customer']->id,
                'message_template_id' => $templates[$template]->id,
                'type' => $type,
                'priority' => 'normal',
                'due_at' => $dueAt,
                'cycle_key' => 'history:'.$type.':'.$months,
                'rendered_message' => 'Mensagem de '.$type.' do '.$fleet[0]['vehicle']->label().'.',
                'status' => 'completed',
                'outcome' => $outcome,
                'completed_at' => $dueAt->copy()->addHours(2),
            ]);
            $this->stamp($task, $dueAt);
        }

        $this->remember($owner, $company, 'Primeiro cliente da oficina', 'Ana Lima cadastrou o Gol ABC1D23.', $fleet[0]['joined']->copy()->addMonths(2)->setTime(11, 0), 'customer.created');
        $this->remember($owner, $company, 'Recibo pago', 'Troca de óleo do Gol ABC1D23 entrou no caixa.', $fleet[0]['joined']->copy()->addMonths(6)->setTime(16, 0), 'receipt.paid');
        $this->remember($owner, $company, 'Campanha concluída', 'Revisão semestral: 3 clientes entraram na fila.', $fleet[0]['joined']->copy()->addMonths(12)->setTime(10, 0), 'campaign.completed');
        $this->remember($owner, $company, 'Horário concluído', 'Revisão do Civic MNO5H67 foi concluída.', $fleet[0]['joined']->copy()->addMonths(16)->setTime(15, 0), 'appointment.completed');
    }

    private function seedUsageHistory(Company $company, Carbon $openedAt): void
    {
        $cursor = $openedAt->copy()->startOfMonth();
        $monthIndex = 0;

        while ($cursor->lte(now()->startOfMonth())) {
            $isCurrentMonth = $cursor->isSameMonth(now());
            UsageRecord::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'period' => $cursor->format('Y-m'),
                'contacts_count' => $isCurrentMonth ? 6 : min(40, 3 + $monthIndex),
                'tasks_count' => $isCurrentMonth ? 1 : min(80, 8 + ($monthIndex * 2)),
            ]);
            $cursor->addMonth();
            $monthIndex++;
        }
    }

    private function remember(User $owner, Company $company, string $title, string $body, Carbon $at, string $event): void
    {
        $owner->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => FilamentDatabaseNotification::class,
            'data' => Notification::make()->title($title)->body($body)->success()->getDatabaseMessage(),
            'read_at' => $at->copy()->addHours(3),
            'created_at' => $at,
            'updated_at' => $at,
        ]);
        $this->stamp(ActivityLog::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'event' => $event,
            'subject_type' => Company::class,
            'subject_id' => $company->id,
            'properties' => ['source' => 'demo'],
        ]), $at);
    }

    private function weekday(Carbon $date): Carbon
    {
        $date = $date->copy()->startOfDay();
        while ($date->isWeekend()) {
            $date->addDay();
        }

        return $date;
    }

    private function stamp(Model $model, Carbon $at): void
    {
        $model->forceFill(['created_at' => $at])->save(['timestamps' => false]);
    }

    private function notify(User $owner, ServiceOrder $order, string $title, string $body, Carbon $at): void
    {
        $owner->notifications()->create([
            'id' => (string) str()->uuid(),
            'type' => FilamentDatabaseNotification::class,
            'data' => Notification::make()
                ->title($title)
                ->warning()
                ->body($body)
                ->actions([
                    Action::make('open')
                        ->label('Abrir ordem')
                        ->button()
                        ->url(ServiceOrderResource::getUrl('edit', ['record' => $order], panel: 'company')),
                ])
                ->getDatabaseMessage(),
            'read_at' => null,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
