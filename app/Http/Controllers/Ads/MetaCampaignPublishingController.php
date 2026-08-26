<?php

namespace App\Http\Controllers\Ads;

use App\Enums\AdCampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ads\PublishMetaCampaignRequest;
use App\Models\AdCampaign;
use App\Services\Meta\Publishing\MetaPublicationValidationService;
use App\Services\Meta\Publishing\MetaPublishingRecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MetaCampaignPublishingController extends Controller
{
    public function confirm(AdCampaign $campaign, MetaPublicationValidationService $validation): View
    {
        Gate::authorize('publish', $campaign);
        $campaign->load(['metaAdAccount', 'metaPage', 'metaInstagramAccount', 'creative', 'audience', 'budget', 'latestPublicationAttempt']);

        return view('campaigns.publish.confirm', ['campaign' => $campaign, 'preflightErrors' => $validation->validate($campaign)]);
    }

    public function publish(PublishMetaCampaignRequest $request, AdCampaign $campaign, MetaPublicationValidationService $validation, MetaPublishingRecoveryService $recovery): RedirectResponse
    {
        if (! in_array($campaign->status, [AdCampaignStatus::Ready, AdCampaignStatus::Failed], true)) {
            return back()->withErrors(['publication' => 'This campaign is not ready to publish.']);
        }
        $errors = $validation->validate($campaign);
        if ($errors->isNotEmpty()) {
            return back()->withErrors($errors)->with('warning', 'Fix the preflight issues before publishing.');
        }

        $recovery->queue($campaign, $request->user());

        return redirect()->route('campaigns.publish.progress', $campaign)->with('status', 'Publication queued. Every Meta object will be created paused.');
    }

    public function progress(AdCampaign $campaign): View
    {
        Gate::authorize('view', $campaign);
        $campaign->load('publicationAttempt');

        return view('campaigns.publish.progress', compact('campaign'));
    }

    public function status(AdCampaign $campaign): JsonResponse
    {
        Gate::authorize('view', $campaign);
        $attempt = $campaign->publicationAttempt;

        return response()->json([
            'campaign_status' => $campaign->status->value,
            'attempt_status' => $attempt?->status->value,
            'stage' => $attempt?->current_stage,
            'retryable' => $attempt?->retryable ?? false,
            'message' => $attempt?->error_message,
            'complete' => $attempt?->completed_at !== null,
            'failed' => $attempt?->failed_at !== null,
        ]);
    }

    public function retry(AdCampaign $campaign, MetaPublishingRecoveryService $recovery): RedirectResponse
    {
        Gate::authorize('publish', $campaign);
        $recovery->queue($campaign, request()->user(), true);

        return redirect()->route('campaigns.publish.progress', $campaign)->with('status', 'Safe publication retry queued. Existing Meta IDs will be reused.');
    }
}
