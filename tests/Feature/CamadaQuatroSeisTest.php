<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Groomer;
use App\Models\Pet;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CamadaQuatroSeisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-21 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_business_break_blocks_appointments_inside_interval(): void
    {
        [$company, $user, $customer, $pet, $service] = $this->baseContext();
        $company->update(['business_breaks' => [['label' => 'Almoço', 'start_hour' => 12, 'start_minute' => 0, 'end_hour' => 13, 'end_minute' => 0]]]);
        $this->actingAs($user);

        $this->expectException(ValidationException::class);
        Appointment::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->next(Carbon::TUESDAY)->setTime(12, 0),
            'status' => 'scheduled',
        ]);
    }

    public function test_two_groomers_can_share_the_same_time_slot(): void
    {
        [$company, $user, $customer, $pet, $service] = $this->baseContext();
        $this->actingAs($user);
        $first = Groomer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Ana']);
        $second = Groomer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Bia']);
        $otherPet = Pet::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'name' => 'Mel']);
        $slot = now()->next(Carbon::TUESDAY)->setTime(10, 0);

        Appointment::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'groomer_id' => $first->id,
            'scheduled_at' => $slot,
            'status' => 'scheduled',
        ]);

        $secondAppointment = Appointment::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $otherPet->id,
            'service_id' => $service->id,
            'groomer_id' => $second->id,
            'scheduled_at' => $slot,
            'status' => 'scheduled',
        ]);

        $this->assertSame('scheduled', $secondAppointment->status);
    }

    public function test_recurring_appointments_are_created_as_a_group(): void
    {
        [$company, $user, $customer, $pet, $service] = $this->baseContext();
        $this->actingAs($user);
        $prototype = new Appointment([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->next(Carbon::TUESDAY)->setTime(10, 0),
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);

        $created = app(AppointmentService::class)->createRecurring($prototype, 1, 3);

        $this->assertCount(3, $created);
        $this->assertNotNull($created[0]->recurrence_group);
        $this->assertSame($created[0]->recurrence_group, $created[2]->recurrence_group);
    }

    public function test_public_booking_and_confirmation_links_work(): void
    {
        [$company, , $customer, $pet, $service] = $this->baseContext();
        $company->forceFill(['public_booking_token' => 'booking-token'])->saveQuietly();

        $this->get('/agendar/booking-token')->assertOk()->assertSee($company->name);

        $this->post('/agendar/booking-token', [
            'customer_name' => 'Nova tutora',
            'customer_phone' => '11988887777',
            'pet_name' => 'Bob',
            'service_id' => $service->id,
            'scheduled_at' => now()->next(Carbon::WEDNESDAY)->setTime(11, 0)->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $appointment = Appointment::withoutGlobalScopes()->where('company_id', $company->id)->whereHas('pet', fn ($q) => $q->where('name', 'Bob'))->first();
        $this->assertNotNull($appointment);
        $this->assertNotNull($appointment->confirmation_token);

        $this->get('/confirmar/'.$appointment->confirmation_token)->assertOk();
        $this->post('/confirmar/'.$appointment->confirmation_token)->assertRedirect();
        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_waitlist_entry_can_be_stored(): void
    {
        [$company, $user, $customer, $pet, $service] = $this->baseContext();
        $this->actingAs($user);

        $entry = WaitlistEntry::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'preferred_date' => today()->addDay(),
            'preferred_time' => '10:00',
            'status' => 'waiting',
        ]);

        $this->assertSame('waiting', $entry->fresh()->status);
    }

    public function test_trial_expiration_command_suspends_company(): void
    {
        [$company, $user] = $this->company();
        $company->update(['status' => 'trial']);
        CompanySubscription::withoutGlobalScopes()->where('company_id', $company->id)->update(['ends_at' => now()->subDay(), 'status' => 'trial']);

        Artisan::call('clientloop:expire-trials');

        $this->assertSame('suspended', $company->fresh()->status);
    }

    public function test_team_member_role_is_stored_on_user(): void
    {
        [$company] = $this->company();
        $member = User::create([
            'company_id' => $company->id,
            'name' => 'Atendente',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password-password',
            'role' => 'attendant',
            'email_verified_at' => now(),
        ]);

        $this->assertSame('attendant', $member->fresh()->role);
    }

    /** @return array{Company, User} */
    private function company(): array
    {
        $plan = Plan::create(['name' => 'Trial', 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => true]);
        $company = Company::create(['name' => 'Loja '.fake()->uuid(), 'slug' => fake()->unique()->slug(), 'status' => 'active']);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now()]);
        $user = User::create(['company_id' => $company->id, 'name' => 'Ana', 'email' => fake()->unique()->safeEmail(), 'password' => 'password-password', 'email_verified_at' => now(), 'role' => 'owner']);

        return [$company, $user];
    }

    /** @return array{Company, User, Customer, Pet, Service} */
    private function baseContext(): array
    {
        [$company, $user] = $this->company();
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Maria', 'phone' => '5511999999999']);
        $pet = Pet::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'name' => 'Thor']);
        $service = Service::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Banho', 'duration_minutes' => 60]);

        return [$company, $user, $customer, $pet, $service];
    }
}
