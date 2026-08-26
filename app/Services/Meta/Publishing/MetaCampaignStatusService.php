<?php

namespace App\Services\Meta\Publishing;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\User;
use App\Notifications\MetaAdvertisementActivated;
use App\Notifications\MetaAdvertisementPaused;
use App\Services\Ads\AdActivityLogger;
use App\Services\Meta\MetaGraphApiClient;
use Illuminate\Validation\ValidationException;

class MetaCampaignStatusService
{
    public function __construct(private MetaGraphApiClient $client, private AdActivityLogger $activity) {}

    public function activate(AdCampaign $campaign, User $user): void
    {
        if ($campaign->budget?->ends_at?->isPast()) {
            throw ValidationException::withMessages(['campaign' => 'This advertisement schedule has expired.']);
        }
        $this->change($campaign, $user, 'ACTIVE');
    }

    public function pause(AdCampaign $campaign, User $user): void
    {
        $this->change($campaign, $user, 'PAUSED');
    }

    private function change(AdCampaign $campaign, User $user, string $status): void
    {
        $campaign->loadMissing(['metaConnection', 'budget']);
        if (! $campaign->meta_ad_id) {
            throw ValidationException::withMessages(['campaign' => 'Publish the advertisement before changing its status.']);
        }
        if (! $campaign->metaConnection?->access_token || $campaign->metaConnection->token_expires_at?->isPast()) {
            throw ValidationException::withMessages(['meta' => 'Reconnect Meta before changing advertisement status.']);
        }

        $this->client->postFormWithToken($campaign->meta_ad_id, $campaign->metaConnection->access_token, ['status' => $status]);
        $campaign->update([
            'status' => $status === 'ACTIVE' ? AdCampaignStatus::Active : AdCampaignStatus::Paused,
            'configured_status' => $status,
            'effective_status' => $status,
            'last_synced_at' => now(),
            'last_error' => null,
        ]);
        $event = $status === 'ACTIVE' ? 'advertisement_activated' : 'advertisement_paused';
        $this->activity->log($campaign, $event, user: $user, context: ['status' => $status]);
        $user->notify($status === 'ACTIVE' ? MetaAdvertisementActivated::for($campaign) : MetaAdvertisementPaused::for($campaign));
    }
}
