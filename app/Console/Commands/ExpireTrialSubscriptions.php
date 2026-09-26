<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanySubscription;
use Illuminate\Console\Command;

class ExpireTrialSubscriptions extends Command
{
    protected $signature = 'clientloop:expire-trials';

    protected $description = 'Suspende empresas em trial cuja assinatura já venceu.';

    public function handle(): int
    {
        CompanySubscription::query()
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->whereHas('company', fn ($query) => $query->where('status', 'trial'))
            ->each(function (CompanySubscription $subscription): void {
                Company::query()->whereKey($subscription->company_id)->update(['status' => 'suspended']);
                $subscription->update(['status' => 'expired']);
                $this->info("Empresa {$subscription->company_id} suspensa por fim de trial.");
            });

        return self::SUCCESS;
    }
}
