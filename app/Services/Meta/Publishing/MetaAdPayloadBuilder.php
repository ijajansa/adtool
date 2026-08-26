<?php

namespace App\Services\Meta\Publishing;

use App\Models\AdCampaign;

class MetaAdPayloadBuilder
{
    public function build(AdCampaign $campaign): array
    {
        return [
            'name' => $campaign->name.' - Ad',
            'adset_id' => $campaign->meta_adset_id,
            'creative' => ['creative_id' => $campaign->creative->meta_creative_id],
            'status' => config('meta_publishing.paused_status'),
        ];
    }
}
