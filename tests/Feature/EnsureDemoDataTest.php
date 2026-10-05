<?php

namespace Tests\Feature;

use App\Filament\Widgets\CompanyOverview;
use App\Filament\Widgets\PlanUsageWidget;
use App\Models\Appointment;
use App\Models\Campaign;
use App\Models\CashEntry;
use App\Models\Company;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\Groomer;
use App\Models\PetPackage;
use App\Models\ServiceOrder;
use App\Models\UsageRecord;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\PackageService;
use Carbon\Carbon;
use Database\Seeders\DemoClinicSeeder;
use Database\Seeders\DemoWorkshopSeeder;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EnsureDemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_demo_data_only_for_an_empty_database(): void
    {
        Artisan::call('clientloop:ensure-demo');

        $this->assertDatabaseHas('users', ['email' => 'demo@clientloop.test']);
        $this->assertDatabaseHas('users', ['email' => 'atendente@clientloop.test', 'role' => 'attendant']);
        $this->assertDatabaseHas('companies', ['slug' => 'petshop-patinhas-demo', 'public_booking_token' => 'patinhas-demo-booking']);
        $this->assertDatabaseHas('users', ['email' => 'oficina@clientloop.test']);
        $this->assertDatabaseHas('companies', ['slug' => 'oficina-centro-demo', 'vertical' => 'automotive']);
    }

    public function test_it_preserves_a_database_that_already_has_a_user(): void
    {
        User::factory()->create(['email' => 'responsavel@petshop.test']);

        Artisan::call('clientloop:ensure-demo');

        $this->assertDatabaseMissing('users', ['email' => 'demo@clientloop.test']);
        $this->assertDatabaseHas('users', ['email' => 'responsavel@petshop.test']);
    }

    public function test_demo_seeder_creates_new_product_surfaces(): void
    {
        $this->seed(DemoClinicSeeder::class);

        $company = Company::query()->where('slug', 'petshop-patinhas-demo')->firstOrFail();

        $this->assertTrue($company->created_at->lte(now()->subMonths(17)));
        $this->assertSame(30, $company->appointment_slot_minutes);
        $this->assertNotEmpty($company->business_breaks);
        $this->assertSame(2, Groomer::withoutGlobalScopes()->where('company_id', $company->id)->count());
        $this->assertSame(1, WaitlistEntry::withoutGlobalScopes()->where('company_id', $company->id)->count());
        $this->assertTrue(Campaign::withoutGlobalScopes()->where('company_id', $company->id)->where('name', 'Retornos da semana')->whereNotNull('starts_at')->exists());
        $this->assertTrue(Campaign::withoutGlobalScopes()->where('company_id', $company->id)->where('name', 'Retornos de inverno')->where('status', 'completed')->exists());
        $this->assertTrue(Appointment::withoutGlobalScopes()->where('company_id', $company->id)->where('status', 'no_show')->exists());
        $this->assertTrue(Appointment::withoutGlobalScopes()->where('company_id', $company->id)->where('recurrence_group', 'demo-nina-recurrence')->count() >= 2);
        $this->assertGreaterThan(40, Appointment::withoutGlobalScopes()->where('company_id', $company->id)->where('status', 'completed')->count());
        $this->assertTrue(
            Appointment::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->whereNotNull('groomer_id')
                ->exists()
        );
        $this->assertDatabaseHas('customers', ['company_id' => $company->id, 'name' => 'Ricardo Mendes']);
        $this->assertDatabaseHas('pets', ['company_id' => $company->id, 'name' => 'Luna']);
        $this->assertGreaterThan(
            0,
            Appointment::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->whereDate('scheduled_at', today())
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count(),
            'Agenda de hoje precisa ter atendimentos no seed demo.',
        );
        $this->get('/book/patinhas-demo-booking')->assertOk()->assertSee('Pet Shop Patinhas');
    }

    public function test_demo_seeder_keeps_eighteen_months_of_staff_notifications(): void
    {
        $this->seed(DemoClinicSeeder::class);

        $owner = User::query()->where('email', 'demo@clientloop.test')->firstOrFail();
        $attendant = User::query()->where('email', 'atendente@clientloop.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@clientloop.test')->firstOrFail();
        $ownerNotes = $owner->notifications()->get();
        $firstImport = $ownerNotes->first(fn ($notification): bool => ($notification->data['title'] ?? null) === 'Importação concluída');

        $this->assertGreaterThanOrEqual(8, $ownerNotes->count());
        $this->assertNotNull($firstImport);
        $this->assertTrue($firstImport->created_at->lte(now()->subMonths(15)));
        $this->assertNotNull($firstImport->read_at);
        $this->assertSame('filament', $firstImport->data['format']);
        $this->assertGreaterThanOrEqual(2, $owner->unreadNotifications()->count());
        $this->assertSame(0, $attendant->notifications()->where('created_at', '<', $attendant->created_at)->count());
        $this->assertGreaterThan(0, $attendant->notifications()->count());
        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_demo_seeder_fills_every_company_overview_card(): void
    {
        $this->seed(DemoClinicSeeder::class);

        $user = User::query()->where('email', 'demo@clientloop.test')->firstOrFail();
        $this->actingAs($user);

        $todayAppointments = Appointment::query()
            ->whereDate('scheduled_at', today())
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();
        $pendingTasks = ContactTask::query()->where('status', 'pending')->count();
        $lateTasks = ContactTask::query()->where('status', 'pending')->where('due_at', '<', now())->count();
        $lowPackages = PetPackage::query()
            ->get()
            ->filter(fn (PetPackage $package): bool => $package->remaining_credits <= 1 && $package->payment_status === 'paid')
            ->count();
        $nextPackageSteps = PetPackage::query()
            ->where('payment_status', 'paid')
            ->get()
            ->filter(fn (PetPackage $package): bool => app(PackageService::class)->nextItem($package) !== null)
            ->count();
        $expiredPackages = PetPackage::query()->whereDate('valid_until', '<', today())->count();

        $this->assertGreaterThan(0, $todayAppointments);
        $this->assertGreaterThan(0, $pendingTasks);
        $this->assertGreaterThan(0, $lateTasks);
        $this->assertGreaterThan(0, $lowPackages);
        $this->assertGreaterThan(0, $nextPackageSteps);
        $this->assertGreaterThan(0, $expiredPackages);
    }

    public function test_demo_seeders_look_like_more_than_eighteen_months_of_use(): void
    {
        $this->seed(DemoClinicSeeder::class);
        $this->seed(DemoWorkshopSeeder::class);

        $clinic = Company::query()->where('slug', 'petshop-patinhas-demo')->firstOrFail();
        $workshop = Company::query()->where('slug', 'oficina-centro-demo')->firstOrFail();
        $openedBefore = now()->subMonths(18);

        $this->assertTrue($clinic->created_at->lte($openedBefore));
        $this->assertTrue($workshop->created_at->lte($openedBefore));
        $this->assertTrue(Carbon::parse(Customer::withoutGlobalScopes()->where('company_id', $clinic->id)->min('created_at'))->lte($openedBefore));
        $this->assertTrue(Carbon::parse(Customer::withoutGlobalScopes()->where('company_id', $workshop->id)->min('created_at'))->lte($openedBefore));
        $this->assertTrue(Carbon::parse(Appointment::withoutGlobalScopes()->where('company_id', $clinic->id)->min('scheduled_at'))->lte(now()->subMonths(17)));
        $this->assertTrue(Carbon::parse(Appointment::withoutGlobalScopes()->where('company_id', $workshop->id)->min('scheduled_at'))->lte(now()->subMonths(17)));
        $this->assertTrue(Carbon::parse(ServiceOrder::withoutGlobalScopes()->where('company_id', $workshop->id)->min('opened_on'))->lte(now()->subMonths(17)));
        $this->assertGreaterThanOrEqual(18, UsageRecord::withoutGlobalScopes()->where('company_id', $clinic->id)->count());
        $this->assertGreaterThanOrEqual(18, UsageRecord::withoutGlobalScopes()->where('company_id', $workshop->id)->count());
        $this->assertTrue(
            CashEntry::withoutGlobalScopes()
                ->where('company_id', $workshop->id)
                ->whereDate('occurred_on', '<', now()->startOfMonth()->toDateString())
                ->exists()
        );
    }

    public function test_demo_workshop_fills_the_automotive_dashboard(): void
    {
        $this->seed(DemoWorkshopSeeder::class);

        $user = User::query()->where('email', 'oficina@clientloop.test')->firstOrFail();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        $overview = collect($this->overviewStats())->mapWithKeys(fn ($stat): array => [$stat->getLabel() => $stat->getValue()])->all();
        $usage = collect($this->planUsageStats())->mapWithKeys(fn ($stat): array => [$stat->getLabel() => $stat->getValue()])->all();

        $this->assertSame([
            'Agenda de hoje' => '1',
            'Tarefas pendentes' => '1',
            'Prontas para entrega' => '2',
            'Em andamento' => '1',
            'Na fila' => '1',
            'A receber' => 'R$ 570,00',
            'Caixa do mês' => 'R$ 250,00',
        ], $overview);
        $this->assertSame([
            'Clientes no mês' => '6 / 500',
            'Tarefas no mês' => '1 / 1000',
        ], $usage);
        $this->assertEqualsCanonicalizing(
            ['Veículo pronto parado', 'Entregue e ainda a receber'],
            $user->unreadNotifications()->get()->map(fn ($notification): string => $notification->data['title'])->all(),
        );
    }

    /** @return array<int, Stat> */
    private function overviewStats(): array
    {
        return (new class extends CompanyOverview
        {
            public function exposed(): array
            {
                return $this->getStats();
            }
        })->exposed();
    }

    /** @return array<int, Stat> */
    private function planUsageStats(): array
    {
        return (new class extends PlanUsageWidget
        {
            public function exposed(): array
            {
                return $this->getStats();
            }
        })->exposed();
    }
}
