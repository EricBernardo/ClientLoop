<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Services\ContactTaskService;
use Illuminate\Console\Command;

class GenerateContactTasks extends Command
{
    protected $signature = 'clientloop:generate-tasks';

    protected $description = 'Gera confirmações, retornos e reativações pendentes.';

    public function handle(ContactTaskService $tasks): int
    {
        Company::query()->whereIn('status', ['trial', 'active'])->each(function (Company $company) use ($tasks) {
            $now = now();
            Appointment::withoutGlobalScopes()->with(['customer', 'service', 'pet', 'vehicle'])->where('company_id', $company->id)->whereIn('status', ['scheduled', 'reschedule_requested'])->whereBetween('scheduled_at', [$now, $now->copy()->addHours($company->confirmation_hours)])->each(function (Appointment $appointment) use ($tasks, $company): void {
                $created = $tasks->create($company, $appointment->customer, 'confirmation', $appointment->scheduled_at->copy()->subHours($company->confirmation_hours), [
                    'appointment' => $appointment,
                    'service' => $appointment->service,
                    'pet' => $appointment->pet,
                    'vehicle' => $appointment->vehicle,
                    'cycle_key' => 'appointment:'.$appointment->id,
                ]);
                if ($created === null) {
                    $pending = ContactTask::withoutGlobalScopes()->where('appointment_id', $appointment->id)->where('type', 'confirmation')->where('status', 'pending')->first();
                    if ($pending) {
                        $tasks->ensureConfirmationLink($pending);
                    }
                }
            });

            Customer::withoutGlobalScopes()->with(['appointments.service', 'appointments.pet', 'appointments.vehicle', 'company'])->where('company_id', $company->id)->whereNull('opted_out_at')->whereDoesntHave('appointments', fn ($query) => $query->whereIn('status', ['scheduled', 'confirmed', 'reschedule_requested'])->where('scheduled_at', '>', $now))->each(function (Customer $customer) use ($tasks, $company, $now): void {
                $last = $customer->appointments->where('status', 'completed')->sortByDesc('scheduled_at')->first();
                $returnAt = $customer->next_return_at ?? ($last?->service?->return_interval_months ? $last->scheduled_at->copy()->addMonths($last->service->return_interval_months) : null);
                if ($returnAt && $returnAt->lte($now)) {
                    $tasks->create($company, $customer, 'recall', $returnAt, [
                        'service' => $last?->service,
                        'pet' => $last?->pet,
                        'vehicle' => $last?->vehicle,
                        'cycle_key' => 'recall:'.($last?->service_id ?? 'general').':'.$returnAt->format('Y-m-d'),
                    ]);
                }
            });

            Customer::withoutGlobalScopes()->where('company_id', $company->id)->whereNull('opted_out_at')->where('last_activity_at', '<=', $now->copy()->subMonths($company->reactivation_months))->whereDoesntHave('appointments', fn ($query) => $query->whereIn('status', ['scheduled', 'confirmed', 'reschedule_requested'])->where('scheduled_at', '>', $now))->each(function (Customer $customer) use ($tasks, $company, $now): void {
                $hasPendingRecall = ContactTask::withoutGlobalScopes()->where('company_id', $company->id)->where('customer_id', $customer->id)->where('type', 'recall')->where('status', 'pending')->exists();
                if ($hasPendingRecall) {
                    return;
                }

                $tasks->create($company, $customer, 'reactivation', $now, ['cycle_key' => 'reactivation:'.$now->format('Y-m')]);
            });
        });

        return self::SUCCESS;
    }
}
