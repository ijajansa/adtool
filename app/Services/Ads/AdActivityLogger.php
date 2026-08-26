<?php

namespace App\Services\Ads;

use App\Models\AdActivityLog;
use App\Models\AdCampaign;
use App\Models\AdPublicationAttempt;
use App\Models\User;

class AdActivityLogger
{
    public function log(AdCampaign $campaign, string $event, ?AdPublicationAttempt $attempt = null, ?User $user = null, array $context = []): void
    {
        AdActivityLog::withoutBusinessScope()->create([
            'business_id' => $campaign->business_id,
            'user_id' => $user?->id,
            'ad_campaign_id' => $campaign->id,
            'ad_publication_attempt_id' => $attempt?->id,
            'event' => $event,
            'context' => $this->safe($context),
        ]);
    }

    private function safe(array $context): array
    {
        return collect($context)->except(['access_token', 'app_secret', 'authorization_code', 'media', 'raw_response'])->all();
    }
}
