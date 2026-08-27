<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsightBackfillRequest;
use App\Jobs\SyncBusinessInsights;
use App\Models\AdCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InsightBackfillController extends Controller
{
    public function create(Request $request): View
    {
        Gate::authorize('manageAnalytics', $request->user()->currentBusiness);

        return view('analytics.backfill', ['campaigns' => AdCampaign::whereNotNull('meta_campaign_id')->orderBy('name')->get()]);
    }

    public function store(InsightBackfillRequest $request): RedirectResponse
    {
        $campaignId = $request->integer('campaign_id') ?: null;
        if ($campaignId && ! AdCampaign::whereKey($campaignId)->exists()) {
            abort(404);
        }
        $from = $request->date('date_from');
        $to = $request->date('date_to');
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $end = $cursor->copy()->addDays(config('ads.insights.api_chunk_days') - 1)->min($to);
            SyncBusinessInsights::dispatch($request->user()->current_business_id, $cursor->toDateString(), $end->toDateString(), $campaignId);
            $cursor = $end->copy()->addDay();
        }

        return back()->with('status', 'Historical insights synchronization queued. Progress appears as daily rows are synchronized.');
    }
}
