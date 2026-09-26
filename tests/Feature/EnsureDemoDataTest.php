<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\ContactTask;
use App\Models\Groomer;
use App\Models\PetPackage;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\PackageService;
use Database\Seeders\DemoClinicSeeder;
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

        $this->assertNotNull($company->hours_configured_at);
        $this->assertNotNull($company->setup_wizard_completed_at);
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
}
