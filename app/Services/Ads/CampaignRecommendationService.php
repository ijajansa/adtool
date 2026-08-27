<?php

namespace App\Services\Ads;

use App\Models\AdCampaign;

class CampaignRecommendationService
{
    public function for(AdCampaign $campaign, array $metrics): array
    {
        $items = [];
        if ((int) str_replace('.', '', $metrics['spend'] ?? '0') > (int) str_replace('.', '', config('ads.insights.recommendations.no_result_spend')) && ($metrics['results'] ?? 0) === 0) {
            $items[] = "This campaign spent {$metrics['spend']} {$campaign->budget?->currency_code} but has no recorded results.";
        }
        if ($campaign->configured_status === 'PAUSED') {
            $items[] = 'The campaign is paused, so it is not currently delivering.';
        }
        if ($campaign->last_error) {
            $items[] = 'Meta reports a delivery or review issue. Review the campaign status before making changes.';
        }
        if (! $campaign->dailyInsights()->where('synced_at', '>=', now()->subHours(config('ads.insights.recommendations.stale_hours')))->exists()) {
            $items[] = 'No recent insights have been synchronized.';
        }

        return $items;
    }
}
