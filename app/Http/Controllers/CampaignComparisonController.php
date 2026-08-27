<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyticsFilterRequest;
use App\Models\AdCampaign;
use App\Services\Meta\Insights\InsightAggregationService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CampaignComparisonController extends Controller
{
    public function index(AnalyticsFilterRequest $request, InsightAggregationService $aggregation): View
    {
        [$from,$to] = $request->range();
        $ids = $request->validated('campaigns', []);
        $campaigns = AdCampaign::with(['budget', 'dailyInsights' => fn ($q) => $q->whereBetween('insight_date', [$from->toDateString(), $to->toDateString()])])->whereIn('id', $ids)->get();
        if (count($ids) !== $campaigns->count()) {
            throw ValidationException::withMessages(['campaigns' => 'One or more campaigns are unavailable.']);
        }
        if ($campaigns->pluck('budget.currency_code')->filter()->unique()->count() > 1) {
            throw ValidationException::withMessages(['campaigns' => 'Compare campaigns using the same currency only.']);
        }
        $metrics = $campaigns->mapWithKeys(fn ($campaign) => [$campaign->id => $aggregation->aggregate($campaign->dailyInsights)]);
        $available = AdCampaign::with('budget')->whereNotNull('meta_campaign_id')->orderBy('name')->get();

        return view('analytics.compare', compact('campaigns', 'metrics', 'available', 'from', 'to'));
    }
}
