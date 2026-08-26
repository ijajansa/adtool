<?php

namespace App\Http\Controllers\Ads;

use App\Exceptions\MetaApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ads\ChangeMetaAdvertisementStatusRequest;
use App\Models\AdCampaign;
use App\Services\Meta\Publishing\MetaCampaignStatusService;
use Illuminate\Http\RedirectResponse;

class MetaCampaignStatusController extends Controller
{
    public function activate(ChangeMetaAdvertisementStatusRequest $request, AdCampaign $campaign, MetaCampaignStatusService $service): RedirectResponse
    {
        try {
            $service->activate($campaign, $request->user());
        } catch (MetaApiException $exception) {
            return back()->withErrors(['meta' => $exception->getMessage()]);
        }

        return back()->with('status', 'Advertisement activated on Meta.');
    }

    public function pause(ChangeMetaAdvertisementStatusRequest $request, AdCampaign $campaign, MetaCampaignStatusService $service): RedirectResponse
    {
        try {
            $service->pause($campaign, $request->user());
        } catch (MetaApiException $exception) {
            return back()->withErrors(['meta' => $exception->getMessage()]);
        }

        return back()->with('status', 'Advertisement paused on Meta.');
    }
}
