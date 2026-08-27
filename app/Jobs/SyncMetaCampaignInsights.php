<?php

namespace App\Jobs;

use App\Exceptions\MetaApiException;
use App\Models\AdCampaign;
use App\Services\Meta\Insights\MetaInsightsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SyncMetaCampaignInsights implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function __construct(public int $campaignId, public string $dateFrom, public string $dateTo) {}

    public function uniqueId(): string
    {
        return "{$this->campaignId}:{$this->dateFrom}:{$this->dateTo}";
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(MetaInsightsService $service): void
    {
        $campaign = AdCampaign::withoutBusinessScope()->find($this->campaignId);
        if (! $campaign) {
            return;
        }
        try {
            $service->sync($campaign, Carbon::parse($this->dateFrom), Carbon::parse($this->dateTo));
        } catch (MetaApiException $exception) {
            if ($exception->retryable()) {
                throw $exception;
            } report($exception);
        }
    }
}
