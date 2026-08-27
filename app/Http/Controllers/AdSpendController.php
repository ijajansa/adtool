<?php

namespace App\Http\Controllers;

use App\Models\CampaignInsightDaily;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdSpendController extends Controller
{
    public function index(Request $request): View
    {
        $rows = CampaignInsightDaily::with('campaign.budget')->whereBetween('insight_date', [now()->startOfMonth()->toDateString(), now()->toDateString()])->orderBy('insight_date')->get();
        $business = $request->user()->currentBusiness->load(['spendingControl', 'selectedMetaAdAccount.latestSnapshot']);

        return view('analytics.spend', compact('rows', 'business'));
    }
}
