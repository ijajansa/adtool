<?php

namespace App\Services\Meta\Publishing;

use App\Models\AdCampaign;
use Illuminate\Support\Str;

class MetaCampaignPayloadBuilder
{
    public function build(AdCampaign $campaign): array
    {
        $mapping = config('meta_publishing.goals.'.$campaign->goal->value);

        return [
            'name' => Str::limit(trim(strip_tags($campaign->name)), 255, ''),
            'objective' => $mapping['objective'],
            'special_ad_categories' => $campaign->special_ad_category_declared ? $campaign->special_ad_categories : [],
            'status' => config('meta_publishing.paused_status'),
        ];
    }
}
