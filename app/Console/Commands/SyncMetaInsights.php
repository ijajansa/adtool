<?php

namespace App\Console\Commands;

use App\Enums\AdCampaignStatus;
use App\Jobs\SyncMetaCampaignInsights;
use App\Models\AdCampaign;
use Illuminate\Console\Command;

class SyncMetaInsights extends Command
{
    protected $signature = 'meta:sync-insights {--completed : Include recently completed campaigns}';

    protected $description = 'Queue Meta campaign insights synchronization';

    public function handle(): int
    {
        $statuses = [AdCampaignStatus::Active, AdCampaignStatus::Paused];
        if ($this->option('completed')) {
            $statuses[] = AdCampaignStatus::Completed;
        }
        AdCampaign::withoutBusinessScope()->whereNotNull('meta_campaign_id')->whereIn('status', $statuses)->select('id')->chunkById(100, function ($campaigns): void {
            foreach ($campaigns as $campaign) {
                SyncMetaCampaignInsights::dispatch($campaign->id, now()->subDays(6)->toDateString(), now()->toDateString());
            }
        });
        $this->info('Insights synchronization queued.');

        return self::SUCCESS;
    }
}
