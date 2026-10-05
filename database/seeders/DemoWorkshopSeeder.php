<?php

namespace Database\Seeders;

use App\Enums\CashEntryDirection;
use App\Enums\CashExpenseCategory;
use App\Enums\CompanyVertical;
use App\Enums\ReceiptPaymentMethod;
use App\Enums\ReceiptStatus;
use App\Enums\ServiceOrderStatus;
use App\Filament\Resources\ServiceOrders\ServiceOrderResource;
use App\Models\CashEntry;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceReceipt;
use App\Models\UsageRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ServiceReceiptService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;

class DemoWorkshopSeeder extends Seeder
{
    public function run(): void
    {
        $openedAt = now()->subYear()->startOfDay();
        $plan = Plan::firstOrCreate(['name' => 'Plano demonstração'], ['contact_limit' => 500, 'task_limit' => 1000, 'is_default' => false]);
        $company = Company::updateOrCreate(['slug' => 'oficina-centro-demo'], [
            'name' => 'Oficina Centro',
            'vertical' => CompanyVertical::Automotive,
            'timezone' => 'America/Sao_Paulo',
            'status' => 'active',
            'business_days' => [1, 2, 3, 4, 5],
            'business_starts_at_hour' => 8,
            'business_ends_at_hour' => 18,
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

        CompanySubscription::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $company->id],
            ['plan_id' => $plan->id, 'status' => 'active', 'starts_at' => $openedAt, 'ends_at' => null],
        );
        UsageRecord::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $company->id, 'period' => now()->format('Y-m')],
            ['contacts_count' => 6, 'tasks_count' => 0],
        );

        $services = $this->services($company);
        $ana = $this->customer($company, 'Ana Lima', '5511981000001');
        $bruno = $this->customer($company, 'Bruno Souza', '5511981000002');
        $carla = $this->customer($company, 'Carla Dias', '5511981000003');
        $diego = $this->customer($company, 'Diego Ramos', '5511981000004');
        $helena = $this->customer($company, 'Helena Cruz', '5511981000005');
        $paulo = $this->customer($company, 'Paulo Reis', '5511981000006');

        $this->order($company, $ana, $this->vehicle($company, $ana, 'ABC1D23', 'Volkswagen', 'Gol'), ServiceOrderStatus::Open, now(), $services['Troca de óleo'], '120.00', 'Cliente deixou o carro de manhã.');
        $this->order($company, $bruno, $this->vehicle($company, $bruno, 'DEF2E34', 'Chevrolet', 'Onix'), ServiceOrderStatus::InProgress, now(), $services['Pastilhas'], '180.00', 'Pastilhas dianteiras em troca.');
        $readyToday = $this->order($company, $carla, $this->vehicle($company, $carla, 'GHI3F45', 'Hyundai', 'HB20'), ServiceOrderStatus::Ready, now(), $services['Alinhamento'], '150.00', 'Alinhamento concluído hoje.');
        $readyWaiting = $this->order($company, $diego, $this->vehicle($company, $diego, 'JKL4G56', 'Fiat', 'Uno'), ServiceOrderStatus::Ready, now()->subDay(), $services['Higienização'], '90.00', 'Pronto desde ontem.');
        $deliveredUnpaid = $this->order($company, $helena, $this->vehicle($company, $helena, 'MNO5H67', 'Honda', 'Civic'), ServiceOrderStatus::Delivered, now()->subDays(2), $services['Revisão'], '420.00', 'Entregue e ainda a prazo.');
        $deliveredPaid = $this->order($company, $paulo, $this->vehicle($company, $paulo, 'PQR7J89', 'Toyota', 'Corolla'), ServiceOrderStatus::Delivered, now()->startOfMonth(), $services['Freios'], '650.00', 'Freios pagos no mês.');

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
    }

    private function purge(Company $company, User $owner): void
    {
        DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $owner->id)
            ->delete();
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
    private function services(Company $company): array
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
                'active' => true,
            ]);
        }

        return $services;
    }

    private function customer(Company $company, string $name, string $phone): Customer
    {
        return Customer::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => $name,
            'phone' => $phone,
        ]);
    }

    private function vehicle(Company $company, Customer $customer, string $plate, string $brand, string $model): Vehicle
    {
        return Vehicle::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'plate' => $plate,
            'brand' => $brand,
            'model' => $model,
        ]);
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
        $order->forceFill(['status_changed_at' => $when])->saveQuietly();

        return $order;
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
