<?php

namespace Tests\Feature;

use App\Filament\Pages\BusinessSettings;
use App\Filament\Pages\Calendar;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\ContactTasks\ContactTaskResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Pets\PetResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Widgets\SetupChecklistWidget;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\DefaultMessageTemplateService;
use App\Support\FirstVisitGuide;
use App\Support\SetupChecklist;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FirstVisitGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_unfinished_company_opens_the_real_hours_screen(): void
    {
        [, $user] = $this->company();

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect(BusinessSettings::getUrl(panel: 'company'));

        $this->get('/admin/services/create')
            ->assertRedirect(BusinessSettings::getUrl(panel: 'company'));

        $page = $this->get('/admin/business-settings')->assertOk();
        $html = $page->getContent();
        $titleAt = strpos($html, 'Horários e regras');
        $guideAt = strpos($html, 'Passo 1 de 7 — Horários');

        $this->assertNotFalse($titleAt);
        $this->assertNotFalse($guideAt);
        $this->assertLessThan($guideAt, $titleAt);
        $page->assertSee('Confirme os dias e o expediente')
            ->assertSee('fi-section', false)
            ->assertSee('1. Horários', false)
            ->assertSee('3. Responsável', false);
    }

    public function test_finished_company_opens_the_panel(): void
    {
        [$company, $user] = $this->company();
        $company->update(['setup_wizard_completed_at' => now()]);

        $this->actingAs($user)
            ->get('/admin/setup')
            ->assertRedirect('/admin');

        $this->get('/admin')->assertOk();
    }

    public function test_unfinished_company_can_log_out(): void
    {
        [, $user] = $this->company();

        $this->actingAs($user)
            ->post('/admin/logout')
            ->assertRedirect();

        $this->assertGuest();
    }

    public function test_attendant_waits_for_the_owner(): void
    {
        [, $user] = $this->company();
        $user->update(['role' => 'attendant']);

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect('/admin/setup');

        $this->get('/admin/setup')
            ->assertOk()
            ->assertSee('Peça ao dono da loja para concluir a configuração.')
            ->assertDontSee('Ir para o painel');
    }

    public function test_first_visit_walks_the_real_screens_until_the_panel_opens(): void
    {
        [$company, $user] = $this->company();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));
        app(DefaultMessageTemplateService::class)->provision($company);

        Livewire::test(BusinessSettings::class)
            ->fillForm([
                'business_days' => [1, 2, 3, 4, 5, 6],
                'business_starts_at_hour' => 9,
                'business_ends_at_hour' => 18,
                'timezone' => 'America/Sao_Paulo',
                'appointment_slot_minutes' => 30,
                'confirmation_hours' => 24,
                'reactivation_months' => 6,
                'business_breaks' => [],
            ])
            ->call('save')
            ->assertRedirect(ServiceResource::getUrl('create', panel: 'company'));

        $this->assertNotNull($company->fresh()->hours_configured_at);
        $this->assertNull($company->fresh()->setup_wizard_completed_at);

        $this->get('/admin/services/create')
            ->assertOk()
            ->assertSee('Passo 2 de 7 — Serviço');

        $service = Service::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Banho',
            'duration_minutes' => 60,
            'return_interval_months' => 1,
            'active' => true,
        ]);

        $this->get('/admin')
            ->assertRedirect(CustomerResource::getUrl('create', panel: 'company'));

        $customer = Customer::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Ana',
            'phone' => '5511999999999',
        ]);

        $this->get('/admin')
            ->assertRedirect(PetResource::getUrl('create', panel: 'company').'?customer_id='.$customer->id);

        $pet = Pet::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => 'Thor',
        ]);

        $this->get('/admin')
            ->assertRedirect(Calendar::getUrl(panel: 'company'));

        $this->get('/admin/calendar')
            ->assertOk()
            ->assertSee('Passo 5 de 7 — Agenda');

        $when = now()->next(Carbon::TUESDAY)->setTime(10, 0);

        Livewire::test(CreateAppointment::class)
            ->set([
                'data.customer_id' => $customer->id,
                'data.pet_id' => $pet->id,
                'data.service_id' => $service->id,
                'data.scheduled_at' => $when->toDateTimeString(),
                'data.duration_minutes' => 60,
            ])
            ->call('create')
            ->assertRedirect(ContactTaskResource::getUrl('index', panel: 'company'));

        $this->assertDatabaseHas('contact_tasks', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'type' => 'confirmation',
            'status' => 'pending',
        ]);
        $this->assertStringContainsString('Ana', (string) ContactTask::query()->value('rendered_message'));

        $this->get('/admin/contact-tasks');

        $this->get('/admin')
            ->assertRedirect(AppointmentResource::getUrl('index', panel: 'company'));

        $appointment = Appointment::query()->firstOrFail();
        app(AppointmentService::class)->complete($appointment);

        $this->assertNotNull($customer->fresh()->next_return_at);

        $html = view('filament.components.first-visit-banner')->render();
        $this->assertStringContainsString('Primeiro atendimento feito', $html);

        $this->post(route('filament.company.first-visit.finish'))
            ->assertRedirect('/admin');

        $company->refresh();
        $this->assertNotNull($company->setup_wizard_completed_at);
        $this->assertNull($company->onboarding_completed_at);
        $this->assertTrue(SetupChecklist::shouldShow($company));

        $user->unsetRelation('company');
        $this->actingAs($user->fresh());

        Livewire::test(SetupChecklistWidget::class)
            ->assertSee('Próximos passos')
            ->assertSee('Criar um modelo de pacote')
            ->assertSee('Ver o guia completo');

        $this->get('/admin')->assertOk();
    }

    public function test_finish_before_completing_the_appointment_stays_on_the_guide(): void
    {
        [$company, $user] = $this->company();
        $company->update(['hours_configured_at' => now()]);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('company'));

        $service = Service::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Banho',
            'duration_minutes' => 60,
            'active' => true,
        ]);
        $customer = Customer::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Ana',
            'phone' => '5511999999999',
        ]);
        $pet = Pet::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => 'Thor',
        ]);
        Appointment::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->next(Carbon::TUESDAY)->setTime(10, 0),
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);
        session([FirstVisitGuide::QUEUE_SESSION => $company->id]);

        $this->post(route('filament.company.first-visit.finish'))
            ->assertRedirect(AppointmentResource::getUrl('index', panel: 'company'));

        $this->assertNull($company->fresh()->setup_wizard_completed_at);
    }

    public function test_finished_company_does_not_get_an_immediate_confirmation_task(): void
    {
        [$company, $user] = $this->company();
        $company->update(['setup_wizard_completed_at' => now(), 'hours_configured_at' => now()]);
        $this->actingAs($user);
        app(DefaultMessageTemplateService::class)->provision($company);

        $service = Service::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Banho',
            'duration_minutes' => 60,
            'active' => true,
        ]);
        $customer = Customer::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Ana',
            'phone' => '5511999999999',
        ]);
        $pet = Pet::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => 'Thor',
        ]);
        $prototype = new Appointment([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->next(Carbon::TUESDAY)->setTime(10, 0),
            'duration_minutes' => 60,
        ]);

        app(AppointmentService::class)->createRecurring($prototype, 1, 1);

        $this->assertDatabaseCount('contact_tasks', 0);
    }

    /**
     * @return array{Company, User}
     */
    private function company(): array
    {
        $plan = Plan::create(['name' => 'Teste', 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => true]);
        $company = Company::create([
            'name' => 'Loja nova '.fake()->uuid(),
            'slug' => fake()->unique()->slug(),
            'status' => 'trial',
        ]);
        CompanySubscription::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => 'trial',
            'starts_at' => now(),
        ]);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Paula',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password-password',
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        return [$company, $user];
    }
}
