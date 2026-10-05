<?php

namespace Tests\Feature;

use App\Enums\CompanyVertical;
use App\Enums\ReceiptPaymentMethod;
use App\Enums\ReceiptStatus;
use App\Enums\ServiceOrderStatus;
use App\Filament\Pages\BusinessSettings;
use App\Filament\Pages\CashFlow;
use App\Filament\Resources\ServiceOrders\Pages\CreateServiceOrder;
use App\Filament\Resources\ServiceOrders\Pages\EditServiceOrder;
use App\Filament\Resources\ServiceOrders\ServiceOrderResource;
use App\Models\CashEntry;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ServiceReceiptService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AutomotiveWorkshopTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_order_saves_items_and_calculates_the_total(): void
    {
        [$company, $user] = $this->company(CompanyVertical::Automotive);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));
        $customer = $this->customer($company, '5511981111111');
        $vehicle = Vehicle::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'plate' => 'abc-1d23',
            'brand' => 'Fiat',
            'model' => 'Uno',
        ]);

        $this->assertSame('ABC1D23', $vehicle->plate);

        Livewire::test(CreateServiceOrder::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'opened_on' => '2026-10-05',
                'status' => ServiceOrderStatus::Open->value,
                'notes' => 'Barulho no freio',
                'items' => [
                    ['description' => 'Troca de óleo', 'quantity' => 1, 'unit_price' => '80,00'],
                    ['description' => 'Filtro', 'quantity' => 2, 'unit_price' => '25,00'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = ServiceOrder::query()->with('items')->firstOrFail();

        $this->assertSame('130.00', $order->totalAmount());
        $this->assertCount(2, $order->items);
        $this->assertSame(ServiceOrderStatus::Open, $order->status);
    }

    public function test_vehicle_from_another_customer_is_rejected(): void
    {
        [$company, $user] = $this->company(CompanyVertical::Automotive);
        $this->actingAs($user);
        $owner = $this->customer($company, '5511981111112');
        $other = $this->customer($company, '5511981111113');
        $vehicle = Vehicle::create([
            'company_id' => $company->id,
            'customer_id' => $owner->id,
            'plate' => 'QWE1A23',
        ]);

        try {
            ServiceOrder::create([
                'company_id' => $company->id,
                'customer_id' => $other->id,
                'vehicle_id' => $vehicle->id,
                'opened_on' => '2026-10-05',
                'status' => ServiceOrderStatus::Open,
            ]);
            $this->fail('A ordem deveria recusar veículo de outro cliente.');
        } catch (ValidationException $exception) {
            $this->assertSame(['O veículo precisa ser do cliente escolhido.'], $exception->errors()['vehicle_id']);
        }
    }

    public function test_paid_receipt_enters_cash_flow_and_pending_removes_it(): void
    {
        [$company, $user] = $this->company(CompanyVertical::Automotive);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));
        $order = $this->order($company);

        Livewire::test(EditServiceOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('lancarRecibo', data: [
                'amount' => '130,00',
                'payment_method' => ReceiptPaymentMethod::Pix->value,
                'status' => ReceiptStatus::Pending->value,
                'issued_on' => '2026-10-05',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(0, CashEntry::query()->count());
        $receipt = $order->fresh()->receipt;
        $this->assertSame(ReceiptStatus::Pending, $receipt->status);
        $this->assertSame('130.00', $receipt->amount);

        Livewire::test(EditServiceOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('marcarPago', data: ['paid_on' => '2026-10-06']);

        $this->assertSame(1, CashEntry::query()->count());
        $this->assertSame('130.00', CashEntry::query()->first()->amount);
        $this->assertSame('2026-10-06', CashEntry::query()->first()->occurred_on->toDateString());

        $this->actingAs($user)->get(route('receipts.show', $receipt))
            ->assertOk()
            ->assertSee('Recibo interno')
            ->assertSee('ABC1D23')
            ->assertSee('Troca de óleo')
            ->assertSee('R$ 130,00');

        Livewire::test(EditServiceOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('voltarPendente');

        $this->assertSame(0, CashEntry::query()->count());
        $this->assertSame(ReceiptStatus::Pending, $receipt->fresh()->status);
    }

    public function test_second_receipt_and_cancelled_order_are_rejected(): void
    {
        [$company, $user] = $this->company(CompanyVertical::Automotive);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));
        $order = $this->order($company);
        app(ServiceReceiptService::class)->issue($order, '130.00', ReceiptPaymentMethod::Cash, ReceiptStatus::Pending, Carbon::parse('2026-10-05'));

        Livewire::test(EditServiceOrder::class, ['record' => $order->fresh()->getRouteKey()])
            ->assertActionHidden('lancarRecibo');

        try {
            app(ServiceReceiptService::class)->issue($order->fresh(), '130.00', ReceiptPaymentMethod::Cash, ReceiptStatus::Pending, Carbon::parse('2026-10-05'));
            $this->fail('A segunda nota deveria ser recusada.');
        } catch (ValidationException $exception) {
            $this->assertSame(['Esta ordem já tem um recibo.'], $exception->errors()['receipt']);
        }

        $cancelled = $this->order($company, 'QWE9Z99');
        $cancelled->update(['status' => ServiceOrderStatus::Cancelled]);

        Livewire::test(EditServiceOrder::class, ['record' => $cancelled->getRouteKey()])
            ->assertActionHidden('lancarRecibo');

        try {
            app(ServiceReceiptService::class)->issue($cancelled->fresh(), '10.00', ReceiptPaymentMethod::Cash, ReceiptStatus::Paid, Carbon::parse('2026-10-05'));
            $this->fail('Ordem cancelada deveria recusar o recibo.');
        } catch (ValidationException $exception) {
            $this->assertSame(['Não é possível lançar recibo de uma ordem cancelada.'], $exception->errors()['status']);
        }
    }

    public function test_other_company_cannot_open_the_receipt(): void
    {
        [$company, $user] = $this->company(CompanyVertical::Automotive);
        $this->actingAs($user);
        $order = $this->order($company);
        $receipt = app(ServiceReceiptService::class)->issue($order, '130.00', ReceiptPaymentMethod::Pix, ReceiptStatus::Paid, Carbon::parse('2026-10-05'));

        [$otherCompany, $otherUser] = $this->company(CompanyVertical::Automotive);
        $this->actingAs($otherUser);

        $this->get(route('receipts.show', $receipt))->assertNotFound();
        $this->assertSame(0, CashEntry::query()->count());
        $this->assertSame(1, CashEntry::withoutGlobalScopes()->where('company_id', $company->id)->count());
        $this->assertNotSame($company->id, $otherCompany->id);
    }

    public function test_manual_expense_updates_the_period_balance(): void
    {
        [$company, $user] = $this->company(CompanyVertical::Automotive);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));
        $order = $this->order($company);
        app(ServiceReceiptService::class)->issue($order, '130.00', ReceiptPaymentMethod::Pix, ReceiptStatus::Paid, Carbon::parse('2026-10-06'));

        Livewire::test(CashFlow::class)
            ->fillForm([
                'from' => '2026-10-01',
                'until' => '2026-10-31',
                'expense_on' => '2026-10-07',
                'category' => 'aluguel',
                'amount' => '50,00',
                'notes' => 'Aluguel do box',
            ])
            ->call('addExpense')
            ->assertHasNoFormErrors()
            ->assertSee('Aluguel')
            ->assertSee('R$ 80,00')
            ->assertSee('Aluguel do box');

        $this->assertDatabaseHas('cash_entries', [
            'company_id' => $company->id,
            'direction' => 'saida',
            'category' => 'aluguel',
            'amount' => '50.00',
        ]);
    }

    public function test_pet_shop_cannot_open_service_orders_or_cash_flow(): void
    {
        [, $user] = $this->company(CompanyVertical::PetShop);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        $this->assertFalse(ServiceOrderResource::canAccess());
        $this->assertFalse(CashFlow::canAccess());
        $this->get(ServiceOrderResource::getUrl('index'))->assertForbidden();
        $this->get(CashFlow::getUrl())->assertForbidden();
    }

    public function test_automotive_business_hours_save_keeps_contact_rules(): void
    {
        [$company, $user] = $this->company(CompanyVertical::Automotive);
        $company->update(['confirmation_hours' => 24, 'reactivation_months' => 6]);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        Livewire::test(BusinessSettings::class)
            ->fillForm([
                'business_days' => [1, 2, 3, 4, 5],
                'business_starts_at_hour' => 8,
                'business_ends_at_hour' => 18,
                'timezone' => 'America/Sao_Paulo',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $company->refresh();

        $this->assertSame(8, $company->business_starts_at_hour);
        $this->assertSame(24, $company->confirmation_hours);
        $this->assertSame(6, $company->reactivation_months);
    }

    /** @return array{Company, User} */
    private function company(CompanyVertical $vertical): array
    {
        $plan = Plan::create(['name' => 'Plano '.$vertical->value.' '.fake()->unique()->numerify('###'), 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => false]);
        $company = Company::create([
            'name' => 'Empresa '.$vertical->value.' '.fake()->unique()->numerify('###'),
            'slug' => fake()->unique()->slug(),
            'status' => 'active',
            'vertical' => $vertical,
        ]);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Dono',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password-password',
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        return [$company, $user];
    }

    private function customer(Company $company, string $phone): Customer
    {
        return Customer::create([
            'company_id' => $company->id,
            'name' => 'Cliente '.$phone,
            'phone' => $phone,
        ]);
    }

    private function order(Company $company, string $plate = 'ABC1D23'): ServiceOrder
    {
        $customer = $this->customer($company, '55119'.fake()->unique()->numerify('#######'));
        $vehicle = Vehicle::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'plate' => $plate,
            'brand' => 'Fiat',
            'model' => 'Uno',
        ]);
        $order = ServiceOrder::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'opened_on' => '2026-10-05',
            'status' => ServiceOrderStatus::Open,
        ]);
        $order->items()->create([
            'company_id' => $company->id,
            'description' => 'Troca de óleo',
            'quantity' => 1,
            'unit_price' => 80,
        ]);
        $order->items()->create([
            'company_id' => $company->id,
            'description' => 'Filtro',
            'quantity' => 2,
            'unit_price' => 25,
        ]);

        return $order->fresh('items');
    }
}
