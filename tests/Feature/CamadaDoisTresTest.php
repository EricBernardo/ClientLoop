<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Campaign;
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
use App\Services\PackageService;
use App\Services\TemplateRenderer;
use App\Support\SetupChecklist;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CamadaDoisTresTest extends TestCase
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

    public function test_no_show_queues_follow_up_recall_task(): void
    {
        [$company, $user, $customer, $pet, $service, $appointment] = $this->appointmentContext();
        $this->actingAs($user);
        MessageTemplate::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Retorno', 'type' => 'recall', 'body' => 'Oi {{responsavel}}, remarcar {{pet}}?', 'active' => true]);

        app(AppointmentService::class)->markNoShow($appointment);

        $this->assertDatabaseHas('contact_tasks', [
            'customer_id' => $customer->id,
            'type' => 'recall',
            'cycle_key' => 'followup:no_show:'.$appointment->id,
            'status' => 'pending',
        ]);
        $this->assertStringContainsString('Thor', ContactTask::query()->where('cycle_key', 'followup:no_show:'.$appointment->id)->value('rendered_message'));
    }

    public function test_recall_generator_passes_last_pet_into_message(): void
    {
        [$company, $user, $customer, $pet, $service] = $this->baseContext();
        $this->actingAs($user);
        MessageTemplate::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Retorno', 'type' => 'recall', 'body' => 'Volta do {{pet}}', 'active' => true]);
        Appointment::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->subWeek()->setTime(10, 0),
            'status' => 'completed',
        ]);
        $customer->update(['next_return_at' => now()->subDay()]);

        Artisan::call('clientloop:generate-tasks');

        $task = ContactTask::query()->where('customer_id', $customer->id)->where('type', 'recall')->first();
        $this->assertNotNull($task);
        $this->assertStringContainsString('Thor', (string) $task->rendered_message);
    }

    public function test_scheduled_campaign_command_launches_due_drafts(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'João', 'phone' => '5511933333333', 'next_return_at' => now()->subDay()]);
        $template = MessageTemplate::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Volte', 'type' => 'recall', 'body' => 'Olá {{responsavel}}', 'active' => true]);
        $campaign = Campaign::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'message_template_id' => $template->id,
            'name' => 'Agendada',
            'type' => 'recall',
            'status' => 'draft',
            'starts_at' => now()->subMinute(),
        ]);

        Artisan::call('clientloop:launch-campaigns');

        $this->assertSame('active', $campaign->fresh()->status);
    }

    public function test_setup_checklist_requires_hours_and_marks_guide_when_viewed(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        Service::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Banho', 'duration_minutes' => 60]);
        $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Ana', 'phone' => '5511999999999']);
        Pet::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'name' => 'Thor']);

        $this->assertTrue(SetupChecklist::shouldShow($company->fresh()));
        $company->update(['hours_configured_at' => now()]);
        $this->assertTrue(SetupChecklist::shouldShow($company->fresh()));

        $steps = SetupChecklist::steps($company->fresh());
        $this->assertFalse(collect($steps)->firstWhere('key', 'guide')['done']);
        $this->assertFalse(collect($steps)->firstWhere('key', 'packages')['done']);
        $company->update(['guide_viewed_at' => now()]);
        $this->assertTrue(collect(SetupChecklist::steps($company->fresh()))->firstWhere('key', 'guide')['done']);
    }

    public function test_package_sale_clears_next_return_and_renewal_task_after_last_credit(): void
    {
        [$company, $user, $customer, $pet, $service] = $this->baseContext();
        $this->actingAs($user);
        $customer->update(['next_return_at' => now()->addWeek()]);
        MessageTemplate::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'Retorno', 'type' => 'recall', 'body' => 'Renovar {{pet}}', 'active' => true]);
        $offer = PackageOffer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => '1 banho', 'credits' => 1]);
        PackageOfferItem::withoutGlobalScopes()->create(['company_id' => $company->id, 'package_offer_id' => $offer->id, 'service_id' => $service->id, 'position' => 1]);
        $package = PetPackage::withoutGlobalScopes()->create(['company_id' => $company->id, 'pet_id' => $pet->id, 'package_offer_id' => $offer->id, 'payment_status' => 'paid', 'purchased_at' => today()]);

        $this->assertNull($customer->fresh()->next_return_at);

        $appointment = Appointment::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'pet_package_id' => $package->id,
            'scheduled_at' => now()->next(Carbon::TUESDAY)->setTime(10, 0),
            'status' => 'confirmed',
        ]);
        app(AppointmentService::class)->complete($appointment);

        $this->assertDatabaseHas('contact_tasks', [
            'customer_id' => $customer->id,
            'cycle_key' => 'package_renewal:'.$package->id,
            'status' => 'pending',
        ]);
    }

    public function test_package_can_be_transferred_between_pets_of_same_customer(): void
    {
        [$company, $user, $customer, $pet, $service] = $this->baseContext();
        $this->actingAs($user);
        $other = Pet::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'name' => 'Mel']);
        $offer = PackageOffer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => '2 banhos', 'credits' => 2]);
        foreach (range(1, 2) as $position) {
            PackageOfferItem::withoutGlobalScopes()->create(['company_id' => $company->id, 'package_offer_id' => $offer->id, 'service_id' => $service->id, 'position' => $position]);
        }
        $package = PetPackage::withoutGlobalScopes()->create(['company_id' => $company->id, 'pet_id' => $pet->id, 'package_offer_id' => $offer->id, 'payment_status' => 'paid', 'purchased_at' => today()]);

        app(PackageService::class)->transfer($package, $other);

        $this->assertSame($other->id, $package->fresh()->pet_id);
    }

    public function test_completed_appointment_can_be_undone_and_restores_package_credit(): void
    {
        [$company, $user, $customer, $pet, $service] = $this->baseContext();
        $this->actingAs($user);
        $offer = PackageOffer::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => '2 banhos', 'credits' => 2]);
        foreach (range(1, 2) as $position) {
            PackageOfferItem::withoutGlobalScopes()->create(['company_id' => $company->id, 'package_offer_id' => $offer->id, 'service_id' => $service->id, 'position' => $position]);
        }
        $package = PetPackage::withoutGlobalScopes()->create(['company_id' => $company->id, 'pet_id' => $pet->id, 'package_offer_id' => $offer->id, 'payment_status' => 'paid', 'purchased_at' => today()]);
        $appointment = Appointment::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'pet_package_id' => $package->id,
            'scheduled_at' => now()->next(Carbon::TUESDAY)->setTime(10, 0),
            'status' => 'confirmed',
        ]);
        app(AppointmentService::class)->complete($appointment);
        $this->assertSame(1, PackageRedemption::query()->where('appointment_id', $appointment->id)->count());

        app(AppointmentService::class)->undoComplete($appointment->fresh());

        $this->assertSame('confirmed', $appointment->fresh()->status);
        $this->assertSame(0, PackageRedemption::query()->where('appointment_id', $appointment->id)->count());
    }

    public function test_booking_link_variable_renders_public_url(): void
    {
        [$company, , $customer] = array_slice($this->baseContext(), 0, 3);
        $company->forceFill(['public_booking_token' => 'token-publico'])->saveQuietly();

        $rendered = app(TemplateRenderer::class)->render('Agende: {{link_agendamento}}', $customer, null, null, null, $company);

        $this->assertStringContainsString('/book/token-publico', $rendered);
    }

    /** @return array{Company, User} */
    private function company(): array
    {
        $plan = Plan::create(['name' => 'Trial', 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => true]);
        $company = Company::create(['name' => 'Loja '.fake()->uuid(), 'slug' => fake()->unique()->slug(), 'status' => 'active', 'setup_wizard_completed_at' => now()]);
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

    /** @return array{Company, User, Customer, Pet, Service, Appointment} */
    private function appointmentContext(): array
    {
        [$company, $user, $customer, $pet, $service] = $this->baseContext();
        $appointment = Appointment::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->next(Carbon::TUESDAY)->setTime(10, 0),
            'status' => 'confirmed',
            'confirmation_token' => 'abc123',
        ]);

        return [$company, $user, $customer, $pet, $service, $appointment];
    }
}
