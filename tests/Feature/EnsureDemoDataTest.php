<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\Groomer;
use App\Models\User;
use App\Models\WaitlistEntry;
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
        $this->assertSame(30, $company->appointment_slot_minutes);
        $this->assertNotEmpty($company->business_breaks);
        $this->assertSame(2, Groomer::withoutGlobalScopes()->where('company_id', $company->id)->count());
        $this->assertSame(1, WaitlistEntry::withoutGlobalScopes()->where('company_id', $company->id)->count());
        $this->assertTrue(Campaign::withoutGlobalScopes()->where('company_id', $company->id)->where('name', 'Retornos da semana')->whereNotNull('starts_at')->exists());
        $this->assertTrue(Appointment::withoutGlobalScopes()->where('company_id', $company->id)->where('status', 'no_show')->exists());
        $this->assertTrue(Appointment::withoutGlobalScopes()->where('company_id', $company->id)->where('recurrence_group', 'demo-nina-recurrence')->count() >= 2);
        $this->assertTrue(
            Appointment::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->whereNotNull('groomer_id')
                ->exists()
        );
        $this->get('/agendar/patinhas-demo-booking')->assertOk()->assertSee('Pet Shop Patinhas');
    }
}
