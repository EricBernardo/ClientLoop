<?php

namespace App\Services;

use App\Models\Company;
use App\Models\UsageRecord;
use Illuminate\Validation\ValidationException;

class QuotaService
{
    public function canCreateContact(Company $company): bool
    {
        return $company->subscription?->plan && $this->record($company)->contacts_count < $company->subscription->plan->contact_limit;
    }

    public function consumeContact(Company $company): void
    {
        if (! $this->canCreateContact($company)) {
            throw ValidationException::withMessages(['quota' => 'Limite mensal de clientes atingido.']);
        } $this->record($company)->increment('contacts_count');
    }

    public function canCreateTasks(Company $company, int $count = 1): bool
    {
        return $company->subscription?->plan && $this->record($company)->tasks_count + $count <= $company->subscription->plan->task_limit;
    }

    public function consumeTasks(Company $company, int $count = 1): void
    {
        if (! $this->canCreateTasks($company, $count)) {
            throw ValidationException::withMessages(['quota' => 'Limite mensal de tarefas atingido.']);
        } $this->record($company)->increment('tasks_count', $count);
    }

    /** @return array{contacts:int,tasks:int,contact_limit:int,task_limit:int,remaining_tasks:int} */
    public function usage(Company $company): array
    {
        $record = $this->record($company);
        $plan = $company->subscription?->plan;

        return [
            'contacts' => $record->contacts_count,
            'tasks' => $record->tasks_count,
            'contact_limit' => $plan?->contact_limit ?? 0,
            'task_limit' => $plan?->task_limit ?? 0,
            'remaining_tasks' => max(0, ($plan?->task_limit ?? 0) - $record->tasks_count),
        ];
    }

    private function record(Company $company): UsageRecord
    {
        return UsageRecord::firstOrCreate(['company_id' => $company->id, 'period' => now()->format('Y-m')]);
    }
}
