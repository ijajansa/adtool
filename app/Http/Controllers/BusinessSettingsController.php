<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessRequest;
use App\Services\BusinessSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BusinessSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $business = $request->user()->currentBusiness;
        Gate::authorize('view', $business);

        return view('businesses.settings', [
            'business' => $business,
            'countries' => config('business.countries'),
            'currencies' => config('business.currencies'),
            'industries' => config('business.industries'),
            'timezones' => timezone_identifiers_list(),
            'canUpdate' => Gate::allows('update', $business),
        ]);
    }

    public function update(UpdateBusinessRequest $request, BusinessSettingsService $service): RedirectResponse
    {
        $service->update(
            $request->user()->currentBusiness,
            $request->validated(),
            $request->file('logo'),
        );

        return redirect()->route('business.settings.edit')->with('status', 'Business settings updated successfully.');
    }
}
