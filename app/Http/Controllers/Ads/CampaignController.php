<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ads\StoreCampaignRequest;
use App\Models\AdCampaign;
use App\Services\Ads\AdWizardService;
use App\Services\Ads\CampaignDuplicationService;
use App\Services\Ads\CreativeMediaService;
use App\Services\Meta\MetaSetupStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AdCampaign::class);
        $campaigns = AdCampaign::query()
            ->with(['budget', 'metaAdAccount'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('goal'), fn ($q) => $q->where('goal', $request->string('goal')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.addcslashes($request->string('search')->toString(), '%_').'%'))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest('updated_at')->paginate(15)->withQueryString();

        return view('campaigns.index', compact('campaigns'));
    }

    public function create(Request $request, MetaSetupStatusService $setup): View|RedirectResponse
    {
        Gate::authorize('create', AdCampaign::class);
        $business = $request->user()->currentBusiness;
        if (! $business->hasCompletedMetaSetup()) {
            return redirect()->route('meta-connection.index')->with('warning', 'Connect Meta and select an ad account and Facebook Page before creating an advertisement.');
        }

        return view('campaigns.wizard.goal', ['campaign' => null, 'goals' => config('ads.goals')]);
    }

    public function store(StoreCampaignRequest $request, AdWizardService $wizard): RedirectResponse
    {
        $campaign = $wizard->create($request->user(), $request->validated());

        return redirect()->route('campaigns.wizard.assets.edit', $campaign)->with('status', 'Draft created. Select the Meta assets to use.');
    }

    public function show(AdCampaign $campaign): View
    {
        Gate::authorize('view', $campaign);
        $campaign->load(['creator', 'metaAdAccount', 'metaPage', 'metaInstagramAccount', 'audience', 'budget', 'creative']);

        return view('campaigns.show', compact('campaign'));
    }

    public function destroy(AdCampaign $campaign, CreativeMediaService $media): RedirectResponse
    {
        Gate::authorize('delete', $campaign);
        $campaign->load('creative');
        $paths = [$campaign->creative?->media_path, $campaign->creative?->thumbnail_path];
        $campaign->delete();
        $media->delete(...$paths);

        return redirect()->route('campaigns.index')->with('status', 'Campaign draft deleted.');
    }

    public function duplicate(AdCampaign $campaign, Request $request, CampaignDuplicationService $service): RedirectResponse
    {
        Gate::authorize('duplicate', $campaign);
        $copy = $service->duplicate($campaign, $request->user());

        return redirect()->route('campaigns.show', $copy)->with('status', 'Campaign duplicated as a new draft.');
    }
}
