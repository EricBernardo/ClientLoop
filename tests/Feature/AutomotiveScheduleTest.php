<?php

namespace Tests\Feature;

use App\Enums\CompanyVertical;
use App\Filament\Pages\Calendar;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Campaigns\CampaignResource;
use App\Filament\Resources\ContactTasks\ContactTaskResource;
use App\Filament\Resources\Pets\PetResource;
use App\Filament\Resources\Vehicles\VehicleResource;
use App\Filament\Resources\WaitlistEntries\WaitlistEntryResource;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DefaultMessageTemplateService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AutomotiveScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_automotive_schedule_pages_open_and_pet_pages_stay_closed(): void
    {
        [, $user] = $this->company(CompanyVertical::Automotive);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        $this->assertTrue(Calendar::canAccess());
        $this->assertTrue(AppointmentResource::canAccess());
        $this->assertTrue(ContactTaskResource::canAccess());
        $this->assertTrue(CampaignResource::canAccess());
        $this->assertTrue(WaitlistEntryResource::canAccess());
        $this->assertTrue(VehicleResource::canAccess());
        $this->assertFalse(PetResource::canAccess());
    }

    public function test_pet_shop_cannot_open_vehicles(): void
    {
        [, $user] = $this->company(CompanyVertical::PetShop);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        $this->assertFalse(VehicleResource::canAccess());
        $this->assertTrue(PetResource::canAccess());
    }

    public function test_public_booking_creates_a_vehicle_and_an_appointment(): void
    {
        [$company] = $this->company(CompanyVertical::Automotive);
        $company->forceFill([
            'public_booking_token' => 'oficina-token',
            'status' => 'active',
            'timezone' => 'America/Sao_Paulo',
            'business_days' => [1, 2, 3, 4, 5],
            'business_starts_at_hour' => 8,
            'business_ends_at_hour' => 18,
            'appointment_slot_minutes' => 60,
        ])->save();
        $service = Service::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Troca de óleo',
            'suggested_price' => 120,
            'duration_minutes' => 60,
            'active' => true,
        ]);

        $this->get(route('booking.show', 'oficina-token'))->assertOk()->assertSee('Placa');

        $this->post(route('booking.store', 'oficina-token'), [
            'customer_name' => 'Ana Lima',
            'customer_phone' => '11988887777',
            'plate' => 'abc-1d23',
            'brand' => 'Fiat',
            'model' => 'Uno',
            'service_id' => $service->id,
            'scheduled_at' => now()->next(Carbon::WEDNESDAY)->setTime(11, 0)->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $vehicle = Vehicle::withoutGlobalScopes()->where('company_id', $company->id)->first();
        $appointment = Appointment::withoutGlobalScopes()->where('company_id', $company->id)->first();

        $this->assertSame('ABC1D23', $vehicle->plate);
        $this->assertSame('Fiat', $vehicle->brand);
        $this->assertSame($vehicle->id, $appointment->vehicle_id);
        $this->assertNull($appointment->pet_id);
    }

    public function test_confirmation_message_includes_the_plate(): void
    {
        $this->travelTo('2026-10-05 08:00:00');
        [$company] = $this->company(CompanyVertical::Automotive);
        $company->update(['confirmation_hours' => 24, 'timezone' => 'America/Sao_Paulo']);
        app(DefaultMessageTemplateService::class)->provision($company);
        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Ana Lima',
            'phone' => '5511988887777',
        ]);
        $vehicle = Vehicle::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'plate' => 'ABC1D23',
            'brand' => 'Fiat',
            'model' => 'Uno',
        ]);
        $service = Service::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Troca de óleo',
            'duration_minutes' => 60,
            'active' => true,
        ]);
        Appointment::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $service->id,
            'scheduled_at' => '2026-10-05 10:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled',
            'confirmation_token' => 'confirma-oficina',
        ]);

        $this->artisan('clientloop:generate-tasks');

        $task = ContactTask::withoutGlobalScopes()->where('company_id', $company->id)->first();

        $this->assertNotNull($task);
        $this->assertStringContainsString('ABC1D23 · Fiat Uno', $task->rendered_message);
        $this->assertStringContainsString('wa.me/5511988887777', (string) $task->whatsappUrl());
    }

    public function test_automotive_appointment_without_a_vehicle_is_rejected(): void
    {
        [$company, $user] = $this->company(CompanyVertical::Automotive);
        $this->actingAs($user);
        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Ana Lima',
            'phone' => '5511988887771',
        ]);

        try {
            Appointment::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'scheduled_at' => '2026-10-05 10:00:00',
                'status' => 'scheduled',
            ]);
            $this->fail('O horário deveria exigir um veículo.');
        } catch (ValidationException $exception) {
            $this->assertSame(['Escolha um veículo do cliente escolhido.'], $exception->errors()['vehicle_id']);
        }
    }

    public function test_pet_shop_appointment_without_a_pet_is_rejected(): void
    {
        [$company, $user] = $this->company(CompanyVertical::PetShop);
        $this->actingAs($user);
        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Maria',
            'phone' => '5511988887772',
        ]);

        try {
            Appointment::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'scheduled_at' => '2026-10-05 10:00:00',
                'status' => 'scheduled',
            ]);
            $this->fail('O horário deveria exigir um pet.');
        } catch (ValidationException $exception) {
            $this->assertSame(['Escolha um pet que pertença ao responsável selecionado.'], $exception->errors()['pet_id']);
        }
    }

    /** @return array{Company, User} */
    private function company(CompanyVertical $vertical): array
    {
        $plan = Plan::create(['name' => 'Plano '.$vertical->value, 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => false]);
        $company = Company::create([
            'name' => 'Empresa '.$vertical->value,
            'slug' => fake()->unique()->slug(),
            'status' => 'active',
            'vertical' => $vertical,
            'timezone' => 'America/Sao_Paulo',
            'business_days' => [1, 2, 3, 4, 5],
            'business_starts_at_hour' => 8,
            'business_ends_at_hour' => 18,
            'appointment_slot_minutes' => 60,
            'confirmation_hours' => 24,
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
}
