<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class LaunchScheduledCampaigns extends Command
{
    protected $signature = 'clientloop:launch-campaigns';

    protected $description = 'Ativa campanhas em rascunho cuja data de início já chegou.';

    public function handle(CampaignService $campaigns): int
    {
        Campaign::query()
            ->where('status', 'draft')
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now())
            ->each(function (Campaign $campaign) use ($campaigns): void {
                try {
                    $campaigns->launch($campaign);
                    $this->info("Campanha {$campaign->id} ativada.");
                } catch (ValidationException $exception) {
                    $this->warn("Campanha {$campaign->id}: ".collect($exception->errors())->flatten()->first());
                }
            });

        return self::SUCCESS;
    }
}
