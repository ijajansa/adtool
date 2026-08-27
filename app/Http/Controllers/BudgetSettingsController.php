<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSpendingControlsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BudgetSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        Gate::authorize('manageAnalytics', $request->user()->currentBusiness);

        return view('analytics.budget-settings', ['business' => $request->user()->currentBusiness->load('spendingControl')]);
    }

    public function update(UpdateSpendingControlsRequest $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness;
        $business->spendingControl()->updateOrCreate([], [...$request->safe()->except('notifications_enabled'), 'currency_code' => $business->currency_code, 'notifications_enabled' => $request->boolean('notifications_enabled')]);

        return back()->with('status', 'Local advertising spending controls updated.');
    }
}
