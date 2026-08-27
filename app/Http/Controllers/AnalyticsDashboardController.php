<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyticsFilterRequest;
use App\Models\AdCampaign;
use App\Models\CampaignInsightDaily;
use App\Services\Meta\Insights\InsightAggregationService;
use App\Services\Meta\MetaSetupStatusService;
use Illuminate\View\View;

class AnalyticsDashboardController extends Controller
{
    public function __invoke(AnalyticsFilterRequest $request, InsightAggregationService $aggregation, MetaSetupStatusService $setup): View
    {
        [$from,$to] = $request->range();
        $business = $request->user()->currentBusiness;
        $business->load(['metaConnection', 'metaAdAccounts', 'metaPages', 'selectedMetaAdAccount', 'selectedMetaPage']);
        $campaigns = AdCampaign::with(['budget', 'metaAdAccount'])->when($request->goal, fn ($q, $v) => $q->where('goal', $v))->when($request->status, fn ($q, $v) => $q->where('status', $v))->when($request->ad_account, fn ($q, $v) => $q->where('meta_ad_account_id', $v))->get();
        $rows = CampaignInsightDaily::with('campaign')->whereIn('ad_campaign_id', $campaigns->pluck('id'))->whereBetween('insight_date', [$from->toDateString(), $to->toDateString()])->orderBy('insight_date')->get();
        $byCurrency = $rows->groupBy('currency_code')->map(fn ($group) => $aggregation->aggregate($group));
        $campaignMetrics = $campaigns->mapWithKeys(fn ($campaign) => [$campaign->id => $aggregation->aggregate($rows->where('ad_campaign_id', $campaign->id))]);

        return view('analytics.dashboard', ['business' => $business, 'metaSetup' => $setup->for($business), 'campaigns' => $campaigns, 'rows' => $rows, 'byCurrency' => $byCurrency, 'campaignMetrics' => $campaignMetrics, 'from' => $from, 'to' => $to, 'lastSynced' => $rows->max('synced_at')]);
    }
}
