<?php

namespace App\Jobs;

use App\Models\AdCampaign;
use App\Services\Meta\MetaCampaignSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMetaCampaignStatus implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 600;

    public function __construct(public int $campaignId) {}

    public function uniqueId(): string
    {
        return 'meta-sync-'.$this->campaignId;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(MetaCampaignSyncService $service): void
    {
        $campaign = AdCampaign::withoutBusinessScope()->find($this->campaignId);
        if ($campaign) {
            $service->sync($campaign);
        }
    }
}
