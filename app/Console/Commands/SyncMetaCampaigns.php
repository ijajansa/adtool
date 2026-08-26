<?php

namespace App\Console\Commands;

use App\Jobs\SyncMetaCampaignStatus;
use App\Models\AdCampaign;
use Illuminate\Console\Command;

class SyncMetaCampaigns extends Command
{
    protected $signature = 'meta:sync-campaigns';

    protected $description = 'Queue status synchronization for published Meta advertisements';

    public function handle(): int
    {
        AdCampaign::withoutBusinessScope()->whereNotNull('meta_ad_id')->select('id')->chunkById(100, function ($campaigns): void {
            foreach ($campaigns as $campaign) {
                SyncMetaCampaignStatus::dispatch($campaign->id);
            }
        });
        $this->info('Meta campaign status synchronization queued.');

        return self::SUCCESS;
    }
}
