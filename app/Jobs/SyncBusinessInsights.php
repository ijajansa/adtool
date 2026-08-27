<?php

namespace App\Jobs;

use App\Models\AdCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncBusinessInsights implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 3600;

    public function __construct(public int $businessId, public string $dateFrom, public string $dateTo, public ?int $campaignId = null) {}

    public function uniqueId(): string
    {
        return "{$this->businessId}:{$this->campaignId}:{$this->dateFrom}:{$this->dateTo}";
    }

    public function handle(): void
    {
        AdCampaign::withoutBusinessScope()->where('business_id', $this->businessId)->whereNotNull('meta_campaign_id')
            ->when($this->campaignId, fn ($query) => $query->whereKey($this->campaignId))->select('id')->chunkById(50, function ($campaigns): void {
                foreach ($campaigns as $campaign) {
                    SyncMetaCampaignInsights::dispatch($campaign->id, $this->dateFrom, $this->dateTo);
                }
            });
    }
}
