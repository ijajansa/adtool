<?php

namespace App\Http\Controllers;

use App\Exceptions\MetaApiException;
use App\Http\Requests\UpdateCampaignBudgetRequest;
use App\Models\AdCampaign;
use App\Services\Meta\Insights\CampaignBudgetUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CampaignBudgetController extends Controller
{
    public function edit(AdCampaign $campaign): View
    {
        Gate::authorize('updateBudget', $campaign);
        $campaign->load(['budget', 'business.spendingControl']);

        return view('analytics.budget-edit', compact('campaign'));
    }

    public function update(UpdateCampaignBudgetRequest $request, AdCampaign $campaign, CampaignBudgetUpdateService $service): RedirectResponse
    {
        try {
            $service->update($campaign, $request->user(), $request->string('amount')->toString(), $request->date('ends_at') ? Carbon::parse($request->validated('ends_at')) : null);
        } catch (MetaApiException $exception) {
            return back()->withInput()->withErrors(['meta' => $exception->getMessage()]);
        }

        return redirect()->route('campaigns.analytics', $campaign)->with('status', 'Campaign budget updated on Meta. Campaign status was not changed.');
    }
}
