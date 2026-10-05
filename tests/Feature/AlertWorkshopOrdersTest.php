<?php

namespace Tests\Feature;

use App\Enums\CompanyVertical;
use App\Enums\ReceiptPaymentMethod;
use App\Enums\ReceiptStatus;
use App\Enums\ServiceOrderStatus;
use App\Filament\Resources\ServiceOrders\ServiceOrderResource;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ServiceReceiptService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertWorkshopOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_vehicle_notifies_the_shop_the_next_morning_once(): void
    {
        $this->travelTo('2026-10-05 15:00:00');
        [$company, $user] = $this->company();
        $other = User::create([
            'company_id' => Company::create(['name' => 'Outra', 'slug' => 'outra-oficina', 'status' => 'active', 'vertical' => CompanyVertical::Automotive])->id,
            'name' => 'Outra',
            'email' => 'outra@example.test',
            'password' => 'password-password',
        ]);
        $superAdmin = User::create([
            'company_id' => $company->id,
            'name' => 'Super',
            'email' => 'super@example.test',
            'password' => 'password-password',
            'is_super_admin' => true,
        ]);
        $order = $this->order($company, 'Maria', 'ABC1D23');
        $order->update(['status' => ServiceOrderStatus::Ready]);

        $this->artisan('clientloop:alert-workshop');

        $this->assertSame(0, $user->notifications()->count());

        $this->travelTo('2026-10-06 08:00:00');
        Filament::setCurrentPanel(Filament::getPanel('company'));
        $this->artisan('clientloop:alert-workshop');
        $this->artisan('clientloop:alert-workshop');

        $notification = $user->fresh()->notifications->sole();
        $this->assertSame('Veículo pronto parado', $notification->data['title']);
        $this->assertSame('ABC1D23 · Fiat Uno de Maria continua pronto para retirada.', $notification->data['body']);
        $this->assertSame(
            ServiceOrderResource::getUrl('edit', ['record' => $order], panel: 'company'),
            $notification->data['actions'][0]['url'],
        );
        $this->assertNotNull($order->fresh()->ready_alerted_at);
        $this->assertSame(0, $other->notifications()->count());
        $this->assertSame(0, $superAdmin->notifications()->count());
    }

    public function test_ready_vehicle_waits_again_after_the_status_changes(): void
    {
        $this->travelTo('2026-10-05 15:00:00');
        [$company, $user] = $this->company();
        $order = $this->order($company, 'Maria', 'ABC1D23');
        $order->update(['status' => ServiceOrderStatus::Ready]);

        $this->travelTo('2026-10-06 08:00:00');
        $this->artisan('clientloop:alert-workshop');
        $order->refresh();
        $order->update(['status' => ServiceOrderStatus::InProgress]);
        $order->update(['status' => ServiceOrderStatus::Ready]);
        $this->artisan('clientloop:alert-workshop');

        $this->assertSame(1, $user->notifications()->count());

        $this->travelTo('2026-10-07 08:00:00');
        $this->artisan('clientloop:alert-workshop');

        $this->assertSame(2, $user->notifications()->count());
    }

    public function test_delivered_order_with_pending_receipt_notifies_the_next_morning(): void
    {
        $this->travelTo('2026-10-05 15:00:00');
        [$company, $user] = $this->company();
        $order = $this->order($company, 'João', 'QWE1A23');
        app(ServiceReceiptService::class)->issue($order, '80.00', ReceiptPaymentMethod::OnAccount, ReceiptStatus::Pending, now());
        $order->update(['status' => ServiceOrderStatus::Delivered]);

        $this->artisan('clientloop:alert-workshop');

        $this->assertSame(0, $user->notifications()->count());

        $this->travelTo('2026-10-06 08:00:00');
        Filament::setCurrentPanel(Filament::getPanel('company'));
        $this->artisan('clientloop:alert-workshop');
        $this->artisan('clientloop:alert-workshop');

        $notification = $user->fresh()->notifications->sole();
        $this->assertSame('Entregue e ainda a receber', $notification->data['title']);
        $this->assertSame('QWE1A23 · Fiat Uno de João foi entregue e o recibo continua pendente.', $notification->data['body']);
        $this->assertSame(
            ServiceOrderResource::getUrl('edit', ['record' => $order], panel: 'company'),
            $notification->data['actions'][0]['url'],
        );
        $this->assertNotNull($order->fresh()->unpaid_delivery_alerted_at);
    }

    public function test_delivered_order_with_paid_receipt_stays_quiet(): void
    {
        $this->travelTo('2026-10-05 15:00:00');
        [$company, $user] = $this->company();
        $order = $this->order($company, 'João', 'QWE1A23');
        app(ServiceReceiptService::class)->issue($order, '80.00', ReceiptPaymentMethod::Pix, ReceiptStatus::Paid, now());
        $order->update(['status' => ServiceOrderStatus::Delivered]);

        $this->travelTo('2026-10-06 08:00:00');
        $this->artisan('clientloop:alert-workshop');

        $this->assertSame(0, $user->notifications()->count());
        $this->assertNull($order->fresh()->unpaid_delivery_alerted_at);
    }

    /** @return array{Company, User} */
    private function company(): array
    {
        $plan = Plan::create(['name' => 'Plano oficina', 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => false]);
        $company = Company::create([
            'name' => 'Oficina '.fake()->unique()->numerify('###'),
            'slug' => fake()->unique()->slug(),
            'status' => 'active',
            'vertical' => CompanyVertical::Automotive,
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

    private function order(Company $company, string $customerName, string $plate): ServiceOrder
    {
        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => $customerName,
            'phone' => '55119'.fake()->unique()->numerify('#######'),
        ]);
        $vehicle = Vehicle::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'plate' => $plate,
            'brand' => 'Fiat',
            'model' => 'Uno',
        ]);

        return ServiceOrder::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'opened_on' => '2026-10-05',
            'status' => ServiceOrderStatus::Open,
        ]);
    }
}
