<?php

namespace App\Http\Controllers;

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

        return view('dashboard.index', [
            'business' => $business,
            'metaSetup' => $setup->for($business),
        ]);
    }
}
