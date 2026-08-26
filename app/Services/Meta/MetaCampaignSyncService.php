<?php

namespace App\Services\Meta;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Notifications\MetaAdvertisementRejected;
use App\Services\Ads\AdActivityLogger;

class MetaCampaignSyncService
{
    public function __construct(private MetaGraphApiClient $client, private AdActivityLogger $activity) {}

    public function sync(AdCampaign $campaign): void
    {
        $campaign->loadMissing(['metaConnection', 'business.activeUsers']);
        if (! $campaign->meta_ad_id || ! $campaign->metaConnection?->access_token) {
            return;
        }
        $statuses = [];
        foreach (['campaign' => $campaign->meta_campaign_id, 'adset' => $campaign->meta_adset_id, 'ad' => $campaign->meta_ad_id] as $type => $id) {
            if ($id) {
                $statuses[$type] = $this->client->get($id, $campaign->metaConnection->access_token, ['fields' => 'configured_status,effective_status,issues_info,review_feedback']);
            }
        }
        $data = $statuses['ad'];
        $configured = strtoupper((string) ($data['configured_status'] ?? $campaign->configured_status));
        $effective = strtoupper((string) ($data['effective_status'] ?? $campaign->effective_status));
        $wasRejected = in_array(strtoupper((string) $campaign->effective_status), ['DISAPPROVED', 'REJECTED'], true);
        $isRejected = in_array($effective, ['DISAPPROVED', 'REJECTED'], true);
        $status = match (true) {
            $isRejected => AdCampaignStatus::Failed,
            $configured === 'ACTIVE' && $effective === 'ACTIVE' => AdCampaignStatus::Active,
            $configured === 'PAUSED' => AdCampaignStatus::Paused,
            default => $campaign->status,
        };
        $campaign->update([
            'configured_status' => $configured ?: null, 'effective_status' => $effective ?: null, 'status' => $status,
            'last_synced_at' => now(), 'last_error' => $isRejected ? 'Meta reported that this advertisement requires attention.' : null,
        ]);
        $safeStatuses = collect($statuses)->map(fn (array $item) => [
            'configured_status' => $item['configured_status'] ?? null,
            'effective_status' => $item['effective_status'] ?? null,
        ])->all();
        $this->activity->log($campaign, 'advertisement_status_synced', context: ['objects' => $safeStatuses]);
        if ($isRejected && ! $wasRejected) {
            $campaign->business->activeUsers->each->notify(MetaAdvertisementRejected::for($campaign));
        }
    }
}
