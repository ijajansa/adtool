<?php

namespace App\Http\Controllers\Ads;

use App\Enums\AdCampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Services\Ads\CampaignValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CampaignReviewController extends Controller
{
    public function show(AdCampaign $campaign): View|RedirectResponse
    {
        Gate::authorize('view', $campaign);
        if ($campaign->current_step < 6) {
            return redirect()->route('campaigns.wizard.budget.edit', $campaign)->with('warning', 'Complete all wizard steps before review.');
        }
        $campaign->load(['metaAdAccount', 'metaPage', 'metaInstagramAccount', 'creative', 'audience', 'budget']);

        return view('campaigns.review', compact('campaign'));
    }

    public function markReady(AdCampaign $campaign, CampaignValidationService $validation): RedirectResponse
    {
        Gate::authorize('publish', $campaign);
        if (! $campaign->isEditable()) {
            abort(403);
        }
        $errors = $validation->validate($campaign);
        if ($errors->isNotEmpty()) {
            $campaign->update(['status' => AdCampaignStatus::Draft]);

            return back()->withErrors($errors)->with('warning', 'The campaign is still a draft. Review the sections below.');
        }
        $campaign->update(['status' => AdCampaignStatus::Ready, 'last_error' => null]);

        return redirect()->route('campaigns.show', $campaign)->with('status', 'Campaign marked ready for publishing. Nothing has been sent to Meta.');
    }
}
