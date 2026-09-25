<?php

namespace Tests\Feature;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\PackageOffer;
use App\Models\PackageOfferItem;
use App\Models\PackageRedemption;
use App\Models\Pet;
use App\Models\PetPackage;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\CsvImportService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PetShopFlowTest extends TestCase
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

    public function test_active_appointments_cannot_overlap_but_can_be_consecutive(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        [$customer, $pet, $service] = $this->petData($company);
        $start = now()->addDays(2)->setTime(10, 0);

        Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'scheduled_at' => $start, 'duration_minutes' => 120, 'status' => 'scheduled']);

        try {
            Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'scheduled_at' => $start->copy()->addHour(), 'duration_minutes' => 60, 'status' => 'scheduled']);
            $this->fail('A sobreposição deveria ser bloqueada.');
        } catch (ValidationException $exception) {
            $this->assertSame(['Este horário já está ocupado. Escolha outro horário disponível.'], $exception->errors()['scheduled_at']);
        }

        $consecutive = Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'scheduled_at' => $start->copy()->addHours(2), 'duration_minutes' => 60, 'status' => 'scheduled']);
        $this->assertSame(60, $consecutive->duration_minutes);
    }

    public function test_appointment_copies_the_service_duration_when_no_duration_is_informed(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        [$customer, $pet] = $this->petData($company);
        $service = Service::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Banho e tosa', 'duration_minutes' => 90]);

        $appointment = Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'scheduled_at' => now()->addDays(5)->setTime(10, 0), 'status' => 'scheduled']);

        $this->assertSame(90, $appointment->duration_minutes);
        $this->assertTrue($appointment->ends_at->equalTo($appointment->scheduled_at->copy()->addMinutes(90)));
    }

    public function test_package_credit_is_consumed_once_only_when_the_appointment_is_completed(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        [$customer, $pet, $service] = $this->petData($company);
        $offer = PackageOffer::withoutGlobalScopes()->create(['company_id' => $company->id, 'service_id' => $service->id, 'name' => '4 banhos', 'credits' => 4, 'suggested_price' => 160, 'active' => true]);
        $package = PetPackage::withoutGlobalScopes()->create(['company_id' => $company->id, 'pet_id' => $pet->id, 'package_offer_id' => $offer->id, 'payment_status' => 'paid', 'purchased_at' => today()]);
        $appointment = Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'pet_package_id' => $package->id, 'scheduled_at' => now()->next('monday')->setTime(10, 0), 'status' => 'confirmed']);

        $this->assertSame(0, PackageRedemption::withoutGlobalScopes()->count());
        app(AppointmentService::class)->complete($appointment);
        app(AppointmentService::class)->complete($appointment->fresh());

        $this->assertSame(1, PackageRedemption::withoutGlobalScopes()->where('appointment_id', $appointment->id)->count());
        $this->assertSame(3, $package->fresh()->remaining_credits);
    }

    public function test_completed_single_appointment_calculates_the_return_from_the_service(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        [$customer, $pet, $service] = $this->petData($company);
        $service->update(['return_interval_months' => 2]);
        $appointment = Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'scheduled_at' => now()->addDays(2)->setTime(10, 0), 'status' => 'confirmed']);

        app(AppointmentService::class)->complete($appointment);

        $customer->refresh();
        $this->assertNotNull($customer->last_activity_at);
        $this->assertNotNull($customer->next_return_at);
        $this->assertTrue($customer->next_return_at->isSameDay(now()->addMonths(2)));
    }

    public function test_scheduled_appointment_can_be_completed_without_a_previous_confirmation(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        [$customer, $pet, $service] = $this->petData($company);
        $appointment = Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'scheduled_at' => now()->addDays(2)->setTime(10, 0), 'status' => 'scheduled']);

        app(AppointmentService::class)->complete($appointment);

        $this->assertSame('completed', $appointment->fresh()->status);
    }

    public function test_an_active_package_clears_the_return_until_its_next_step_is_completed(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        [$customer, $pet, $service] = $this->petData($company);
        $service->update(['return_interval_months' => 1]);
        $customer->update(['next_return_at' => now()->subDay()]);
        $offer = PackageOffer::withoutGlobalScopes()->create(['company_id' => $company->id, 'service_id' => $service->id, 'name' => '2 banhos', 'credits' => 2]);
        $package = PetPackage::withoutGlobalScopes()->create(['company_id' => $company->id, 'pet_id' => $pet->id, 'package_offer_id' => $offer->id, 'payment_status' => 'paid', 'purchased_at' => today()]);
        $appointment = Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'pet_package_id' => $package->id, 'scheduled_at' => now()->addDays(2)->setTime(10, 0), 'status' => 'confirmed']);

        app(AppointmentService::class)->complete($appointment);

        $this->assertNull($customer->fresh()->next_return_at);
    }

    public function test_pending_or_expired_package_cannot_be_attached_to_an_appointment(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        [$customer, $pet, $service] = $this->petData($company);
        $offer = PackageOffer::withoutGlobalScopes()->create(['company_id' => $company->id, 'service_id' => $service->id, 'name' => '2 banhos', 'credits' => 2]);
        $package = PetPackage::withoutGlobalScopes()->create(['company_id' => $company->id, 'pet_id' => $pet->id, 'package_offer_id' => $offer->id, 'payment_status' => 'pending', 'purchased_at' => today()]);

        $this->expectException(ValidationException::class);
        Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'pet_package_id' => $package->id, 'scheduled_at' => now()->addDays(4)->setTime(10, 0), 'status' => 'scheduled']);
    }

    public function test_business_hours_block_an_appointment_that_runs_past_five_pm(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        [$customer, $pet] = $this->petData($company);
        $grooming = Service::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Tosa', 'duration_minutes' => 120]);
        $monday = now()->next('monday')->setTime(15, 0);

        $appointment = Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $grooming->id, 'scheduled_at' => $monday, 'status' => 'scheduled']);
        $this->assertSame(120, $appointment->duration_minutes);

        $this->expectException(ValidationException::class);
        Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $grooming->id, 'scheduled_at' => $monday->copy()->setTime(16, 0), 'status' => 'scheduled']);
    }

    public function test_create_appointment_page_shows_a_field_error_for_an_invalid_schedule(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        [$customer, $pet, $service] = $this->petData($company);
        Filament::setCurrentPanel('company');

        Livewire::test(CreateAppointment::class)
            ->set([
                'data.customer_id' => $customer->id,
                'data.pet_id' => $pet->id,
                'data.service_id' => $service->id,
                'data.scheduled_at' => now()->next('sunday')->setTime(10, 0)->toDateTimeString(),
                'data.duration_minutes' => 60,
            ])
            ->call('create')
            ->assertHasErrors(['data.scheduled_at' => 'Não há atendimento neste dia. Escolha um dia de segunda a sábado.']);
    }

    public function test_service_price_mask_is_normalized_before_saving(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        Filament::setCurrentPanel('company');

        Livewire::test(CreateService::class)
            ->set([
                'data.name' => 'Banho especial',
                'data.suggested_price' => '4.564,65',
                'data.duration_minutes' => 60,
                'data.active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $service = Service::withoutGlobalScopes()->where('company_id', $company->id)->where('name', 'Banho especial')->firstOrFail();

        $this->assertSame('4564.65', $service->suggested_price);
    }

    public function test_sequential_package_requires_the_next_service_and_prepares_the_next_week(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        [$customer, $pet, $bath] = $this->petData($company);
        $completeBath = Service::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Banho completo', 'duration_minutes' => 60]);
        $offer = PackageOffer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Pacote sequencial', 'credits' => 2, 'suggested_price' => 100]);
        PackageOfferItem::withoutGlobalScopes()->create(['company_id' => $company->id, 'package_offer_id' => $offer->id, 'service_id' => $bath->id, 'position' => 1]);
        PackageOfferItem::withoutGlobalScopes()->create(['company_id' => $company->id, 'package_offer_id' => $offer->id, 'service_id' => $completeBath->id, 'position' => 2]);
        $package = PetPackage::withoutGlobalScopes()->create(['company_id' => $company->id, 'pet_id' => $pet->id, 'package_offer_id' => $offer->id, 'payment_status' => 'paid', 'purchased_at' => today()]);
        $appointment = Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $bath->id, 'pet_package_id' => $package->id, 'scheduled_at' => now()->next('monday')->setTime(10, 0), 'status' => 'confirmed']);

        app(AppointmentService::class)->complete($appointment);

        $nextItem = $package->fresh()->items()->whereDoesntHave('redemption')->orderBy('position')->first();
        $this->assertSame($completeBath->id, $nextItem->service_id);
        $url = AppointmentResource::nextPackageAppointmentUrl($appointment->fresh());
        $this->assertStringContainsString('service_id='.$completeBath->id, $url);
        $this->assertStringContainsString('pet_package_id='.$package->id, $url);
    }

    public function test_confirmation_uses_pet_and_responsible_variables_once(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        [$customer, $pet, $service] = $this->petData($company);
        MessageTemplate::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Confirmar banho', 'type' => 'confirmation', 'body' => 'Olá {{responsavel}}, o banho de {{pet}} é {{data}} às {{horario}}.']);
        $appointment = Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'scheduled_at' => now()->addDay()->setTime(10, 0), 'status' => 'scheduled']);

        Artisan::call('clientloop:generate-tasks');
        Artisan::call('clientloop:generate-tasks');

        $task = ContactTask::withoutGlobalScopes()->where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertStringContainsString($customer->name, $task->rendered_message);
        $this->assertStringContainsString($pet->name, $task->rendered_message);
        $this->assertSame(1, ContactTask::withoutGlobalScopes()->where('appointment_id', $appointment->id)->count());
    }

    public function test_import_creates_responsible_and_pet_and_accepts_legacy_customer_headers(): void
    {
        Storage::fake('local');
        [$company] = $this->company();
        Storage::disk('local')->put('imports/pets.csv', "responsible_name,responsible_phone,pet_name\nAna Silva,(11) 99999-9999,Thor\n");
        $result = app(CsvImportService::class)->customers($company, Storage::disk('local')->path('imports/pets.csv'), ['responsible_name' => 'responsible_name', 'responsible_phone' => 'responsible_phone', 'pet_name' => 'pet_name']);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('customers', ['company_id' => $company->id, 'name' => 'Ana Silva']);
        $this->assertDatabaseHas('pets', ['company_id' => $company->id, 'name' => 'Thor']);
    }

    public function test_pet_and_package_pages_are_scoped_to_the_current_company(): void
    {
        [$first, $user] = $this->company();
        [$second] = $this->company();
        Customer::withoutGlobalScopes()->create(['company_id' => $second->id, 'name' => 'Oculto', 'phone' => '5511987878787']);
        $this->actingAs($user);

        $this->assertSame(0, Pet::query()->count());
        $this->get('/admin/calendar')->assertOk()->assertSee('Novo agendamento');
        $this->get('/admin/business-settings')->assertOk()->assertSee('Horários de atendimento');
        $this->get('/admin/pets')->assertOk()->assertSee('Pets');
        $this->get('/admin/pet-packages')->assertOk()->assertSee('Pacotes');
    }

    /** @return array{Company, User} */
    private function company(): array
    {
        $plan = Plan::firstOrCreate(['name' => 'Pet test'], ['contact_limit' => 100, 'task_limit' => 100, 'is_default' => true]);
        $company = Company::create(['name' => 'Pet shop '.fake()->uuid(), 'slug' => fake()->unique()->slug(), 'status' => 'active']);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active']);
        $user = User::create(['company_id' => $company->id, 'name' => 'Ana', 'email' => fake()->unique()->safeEmail(), 'password' => 'password-password', 'email_verified_at' => now()]);

        return [$company, $user];
    }

    /** @return array{Customer, Pet, Service} */
    private function petData(Company $company): array
    {
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Maria', 'phone' => '5511999999999']);
        $pet = Pet::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'name' => 'Thor']);
        $service = Service::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Banho', 'duration_minutes' => 60]);

        return [$customer, $pet, $service];
    }
}
