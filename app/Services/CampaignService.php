<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Customer;
use Illuminate\Validation\ValidationException;

class CampaignService
{
    public function __construct(
        private ContactTaskService $tasks,
        private QuotaService $quota,
        private StaffNotifier $notifier,
    ) {}

    public function launch(Campaign $campaign): int
    {
        if (! in_array($campaign->type, ['recall', 'reactivation'], true)) {
            throw ValidationException::withMessages(['campaign' => 'Tipo de campanha inválido.']);
        }

        $company = $campaign->company;
        $customers = $this->eligibleCustomers($campaign)->get();

        if (! $this->quota->canCreateTasks($company, $customers->count())) {
            throw ValidationException::withMessages(['quota' => 'A campanha excede a cota mensal de tarefas disponível.']);
        }

        $created = 0;
        foreach ($customers as $customer) {
            $recipient = CampaignRecipient::withoutGlobalScopes()->firstOrCreate([
                'company_id' => $company->id,
                'campaign_id' => $campaign->id,
                'customer_id' => $customer->id,
            ]);

            if (! $recipient->wasRecentlyCreated) {
                continue;
            }

            $task = $this->tasks->create($company, $customer, $campaign->type, $campaign->starts_at ?? now(), ['campaign' => $campaign], $campaign->messageTemplate);
            $recipient->update(['contact_task_id' => $task?->id, 'status' => $task ? 'queued' : 'skipped']);
            $created += (int) ($task !== null);
        }

        $campaign->update(['status' => 'active', 'starts_at' => $campaign->starts_at ?? now()]);
        $this->notifier->campaignLaunched($campaign, $created);

        return $created;
    }

    private function eligibleCustomers(Campaign $campaign)
    {
        $query = Customer::withoutGlobalScopes()->where('company_id', $campaign->company_id)->whereNull('opted_out_at');

        if ($campaign->type === 'recall') {
            $months = data_get($campaign->filters, 'return_months');

            return $query->whereDoesntHave('appointments', fn ($q) => $q->whereIn('status', ['scheduled', 'confirmed', 'reschedule_requested'])->where('scheduled_at', '>', now()))->when($months, fn ($q) => $q->whereBetween('next_return_at', [now()->subMonths((int) $months), now()]), fn ($q) => $q->where('next_return_at', '<=', now()));
        }

        return $query->whereNotNull('last_activity_at')->where('last_activity_at', '<=', now()->subMonths($campaign->company->reactivation_months))->whereDoesntHave('appointments', fn ($q) => $q->whereIn('status', ['scheduled', 'confirmed', 'reschedule_requested'])->where('scheduled_at', '>', now()));
    }
}
