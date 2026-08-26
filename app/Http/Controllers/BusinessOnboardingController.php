<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessRequest;
use App\Services\BusinessOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessOnboardingController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $business = $request->user()->businesses()
            ->active()
            ->wherePivot('status', true)
            ->first();

        if ($business) {
            $request->user()->forceFill(['current_business_id' => $business->id])->save();

            return redirect()->route('dashboard');
        }

        return view('businesses.onboarding', [
            'countries' => config('business.countries'),
            'currencies' => config('business.currencies'),
            'industries' => config('business.industries'),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function store(StoreBusinessRequest $request, BusinessOnboardingService $service): RedirectResponse
    {
        $service->createFor($request->user(), $request->validated());

        return redirect()->route('dashboard')->with('status', 'Business profile created successfully.');
    }
}
