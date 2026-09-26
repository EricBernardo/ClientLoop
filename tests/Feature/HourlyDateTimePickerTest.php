<?php

namespace Tests\Feature;

use App\Filament\Forms\Components\HourlyDateTimePicker;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HourlyDateTimePickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_offers_only_full_hour_time_slots_by_default(): void
    {
        $picker = HourlyDateTimePicker::make('scheduled_at');

        $this->assertSame(1, $picker->getHoursStep());
        $this->assertSame(60, $picker->getMinutesStep());
        $this->assertFalse($picker->hasSeconds());
    }

    public function test_it_uses_company_appointment_slot_minutes(): void
    {
        $plan = Plan::create(['name' => 'Trial', 'contact_limit' => 10, 'task_limit' => 10, 'is_default' => true]);
        $company = Company::create(['name' => 'Slots', 'slug' => 'slots', 'status' => 'active', 'appointment_slot_minutes' => 15]);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active']);
        $user = User::create(['company_id' => $company->id, 'name' => 'Ana', 'email' => 'slots@example.test', 'password' => 'password-password', 'email_verified_at' => now()]);
        $this->actingAs($user);

        $picker = HourlyDateTimePicker::make('scheduled_at');

        $this->assertSame(15, $picker->getMinutesStep());
    }
}
