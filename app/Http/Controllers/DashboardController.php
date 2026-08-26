<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Services\Meta\MetaSetupStatusService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MetaSetupStatusService $setup): View
    {
        $business = $request->user()->currentBusiness;
        $business->load([
            'metaConnection',
            'metaAdAccounts',
            'metaPages',
            'selectedMetaAdAccount',
            'selectedMetaPage',
        ]);
        $campaignCounts = AdCampaign::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $recentCampaigns = AdCampaign::query()
            ->with(['budget', 'metaAdAccount'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return view('dashboard.index', [
            'business' => $business,
            'metaSetup' => $setup->for($business),
            'campaignCounts' => $campaignCounts,
            'recentCampaigns' => $recentCampaigns,
        ]);
    }
}
