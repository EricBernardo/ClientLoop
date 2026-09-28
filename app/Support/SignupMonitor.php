<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanySubscription;
use Illuminate\Database\Eloquent\Builder;

class SignupMonitor
{
    public static function onTrial(Builder $query): Builder
    {
        return $query->where('status', 'trial');
    }

    public static function trialEndingSoon(Builder $query): Builder
    {
        return $query->where('status', 'trial')->whereHas('subscription', function (Builder $subscription): void {
            $subscription->whereNotNull('ends_at')->whereBetween('ends_at', [now(), now()->copy()->addDays(3)]);
        });
    }

    public static function signedUpRecently(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDays(7));
    }

    public static function unusedTrials(Builder $query): Builder
    {
        return $query->where('status', 'trial')->whereDoesntHave('customers')->whereDoesntHave('appointments');
    }

    public static function quotaExhausted(Builder $query): Builder
    {
        $period = now()->format('Y-m');

        return $query->whereExists(function ($usage) use ($period): void {
            $usage->selectRaw('1')
                ->from('usage_records')
                ->join('company_subscriptions', 'company_subscriptions.company_id', '=', 'usage_records.company_id')
                ->join('plans', 'plans.id', '=', 'company_subscriptions.plan_id')
                ->whereColumn('usage_records.company_id', 'companies.id')
                ->where('usage_records.period', $period)
                ->where(function ($limits): void {
                    $limits->whereColumn('usage_records.contacts_count', '>=', 'plans.contact_limit')
                        ->orWhereColumn('usage_records.tasks_count', '>=', 'plans.task_limit');
                });
        });
    }

    public static function suspended(Builder $query): Builder
    {
        return $query->where('status', 'suspended');
    }

    /** @return array{trial: int, ending: int, recent: int, unused: int, quota: int, suspended: int} */
    public static function counts(): array
    {
        return [
            'trial' => self::onTrial(Company::query())->count(),
            'ending' => self::trialEndingSoon(Company::query())->count(),
            'recent' => self::signedUpRecently(Company::query())->count(),
            'unused' => self::unusedTrials(Company::query())->count(),
            'quota' => self::quotaExhausted(Company::query())->count(),
            'suspended' => self::suspended(Company::query())->count(),
        ];
    }

    public static function usageLabel(Company $company): string
    {
        $plan = $company->subscription?->plan;
        if (! $plan) {
            return '—';
        }

        $contacts = (int) ($company->currentUsage?->contacts_count ?? 0);
        $tasks = (int) ($company->currentUsage?->tasks_count ?? 0);

        return "{$contacts}/{$plan->contact_limit} · {$tasks}/{$plan->task_limit}";
    }

    public static function extendTrial(Company $company): void
    {
        $subscription = CompanySubscription::withoutGlobalScopes()->where('company_id', $company->id)->first();
        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => 'trial',
            'ends_at' => ($subscription->ends_at ?? now())->copy()->addDays(7),
        ]);
        $company->update(['status' => 'trial']);
    }
}
