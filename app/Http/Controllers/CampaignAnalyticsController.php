<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyticsFilterRequest;
use App\Models\AdCampaign;
use App\Services\Ads\CampaignRecommendationService;
use App\Services\Meta\Insights\InsightAggregationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CampaignAnalyticsController extends Controller
{
    public function show(AnalyticsFilterRequest $request, AdCampaign $campaign, InsightAggregationService $aggregation, CampaignRecommendationService $recommendations): View
    {
        Gate::authorize('view', $campaign);
        [$from,$to] = $request->range();
        $campaign->load(['budget', 'dailyInsights' => fn ($q) => $q->whereBetween('insight_date', [$from->toDateString(), $to->toDateString()])->orderBy('insight_date')]);
        $metrics = $aggregation->aggregate($campaign->dailyInsights);

        return view('analytics.campaign', compact('campaign', 'metrics', 'from', 'to') + ['recommendations' => $recommendations->for($campaign, $metrics)]);
    }
}
