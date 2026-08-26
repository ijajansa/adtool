<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ads\UpdateCampaignAssetsRequest;
use App\Http\Requests\Ads\UpdateCampaignAudienceRequest;
use App\Http\Requests\Ads\UpdateCampaignBudgetRequest;
use App\Http\Requests\Ads\UpdateCampaignCreativeRequest;
use App\Http\Requests\Ads\UpdateCampaignGoalRequest;
use App\Models\AdCampaign;
use App\Models\MetaConnection;
use App\Services\Ads\AdWizardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdWizardController extends Controller
{
    public function editGoal(AdCampaign $campaign): View
    {
        Gate::authorize('update', $campaign);

        return view('campaigns.wizard.goal', ['campaign' => $campaign, 'goals' => config('ads.goals')]);
    }

    public function updateGoal(UpdateCampaignGoalRequest $request, AdCampaign $campaign, AdWizardService $wizard): RedirectResponse
    {
        $wizard->updateGoal($campaign, $request->validated());

        return redirect()->route('campaigns.wizard.assets.edit', $campaign)->with('status', 'Goal saved.');
    }

    public function editAssets(AdCampaign $campaign): View|RedirectResponse
    {
        Gate::authorize('update', $campaign);
        if ($redirect = $this->guardStep($campaign, 2)) {
            return $redirect;
        }
        $business = $campaign->business;
        $connection = $business->metaConnection;
        if (! $connection || $connection->status !== MetaConnection::STATUS_CONNECTED) {
            return redirect()->route('meta-connection.index')->with('warning', 'Reconnect Meta before selecting campaign assets.');
        }

        return view('campaigns.wizard.assets', [
            'campaign' => $campaign,
            'adAccounts' => $business->metaAdAccounts()->where('meta_connection_id', $connection->id)->orderBy('name')->get(),
            'pages' => $business->metaPages()->where('meta_connection_id', $connection->id)->with('instagramAccounts')->orderBy('name')->get(),
            'instagramAccounts' => $business->metaInstagramAccounts()->where('meta_connection_id', $connection->id)->orderBy('username')->get(),
        ]);
    }

    public function updateAssets(UpdateCampaignAssetsRequest $request, AdCampaign $campaign, AdWizardService $wizard): RedirectResponse
    {
        $wizard->updateAssets($campaign, $request->validated());

        return redirect()->route('campaigns.wizard.creative.edit', $campaign)->with('status', 'Meta assets saved.');
    }

    public function editCreative(AdCampaign $campaign): View|RedirectResponse
    {
        Gate::authorize('update', $campaign);
        if ($redirect = $this->guardStep($campaign, 3)) {
            return $redirect;
        }
        $campaign->load(['creative', 'metaPage', 'metaInstagramAccount']);

        return view('campaigns.wizard.creative', ['campaign' => $campaign, 'ctas' => config('ads.goals.'.$campaign->goal->value.'.ctas')]);
    }

    public function updateCreative(UpdateCampaignCreativeRequest $request, AdCampaign $campaign, AdWizardService $wizard): RedirectResponse
    {
        $wizard->updateCreative($campaign, $request->validated(), $request->file('media'));

        return redirect()->route('campaigns.wizard.audience.edit', $campaign)->with('status', 'Creative saved.');
    }

    public function editAudience(AdCampaign $campaign): View|RedirectResponse
    {
        Gate::authorize('update', $campaign);
        if ($redirect = $this->guardStep($campaign, 4)) {
            return $redirect;
        }
        $campaign->load('audience');

        return view('campaigns.wizard.audience', compact('campaign'));
    }

    public function updateAudience(UpdateCampaignAudienceRequest $request, AdCampaign $campaign, AdWizardService $wizard): RedirectResponse
    {
        $wizard->updateAudience($campaign, $request->validated());

        return redirect()->route('campaigns.wizard.budget.edit', $campaign)->with('status', 'Audience saved.');
    }

    public function editBudget(AdCampaign $campaign): View|RedirectResponse
    {
        Gate::authorize('update', $campaign);
        if ($redirect = $this->guardStep($campaign, 5)) {
            return $redirect;
        }
        $campaign->load(['budget', 'metaAdAccount']);
        if (blank($campaign->metaAdAccount?->currency) || blank($campaign->metaAdAccount?->timezone_name)) {
            return redirect()->route('meta-connection.index')->with('warning', 'The selected ad account is missing currency or timezone data. Synchronize Meta assets before setting a budget.');
        }
        $timezone = $campaign->metaAdAccount->timezone_name ?: 'UTC';

        return view('campaigns.wizard.budget', compact('campaign', 'timezone'));
    }

    public function updateBudget(UpdateCampaignBudgetRequest $request, AdCampaign $campaign, AdWizardService $wizard): RedirectResponse
    {
        $wizard->updateBudget($campaign, $request->validated());

        return redirect()->route('campaigns.review', $campaign)->with('status', 'Budget and schedule saved.');
    }

    private function guardStep(AdCampaign $campaign, int $requiredStep): ?RedirectResponse
    {
        if ($campaign->current_step >= $requiredStep) {
            return null;
        }
        $routes = [1 => 'campaigns.wizard.goal.edit', 2 => 'campaigns.wizard.assets.edit', 3 => 'campaigns.wizard.creative.edit', 4 => 'campaigns.wizard.audience.edit', 5 => 'campaigns.wizard.budget.edit'];
        $step = max(1, min(5, $campaign->current_step));

        return redirect()->route($routes[$step], $campaign)->with('warning', 'Complete this step before continuing.');
    }
}
