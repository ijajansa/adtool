<?php

namespace App\Services\Meta\Publishing;

use App\Enums\AdCampaignStatus;
use App\Enums\AdPublicationStatus;
use App\Jobs\PublishMetaCampaign;
use App\Models\AdCampaign;
use App\Models\AdPublicationAttempt;
use App\Models\User;
use App\Services\Ads\AdActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MetaPublishingRecoveryService
{
    public function __construct(private AdActivityLogger $activity) {}

    public function queue(AdCampaign $campaign, User $user, bool $retry = false): AdPublicationAttempt
    {
        $attempt = DB::transaction(function () use ($campaign, $user, $retry): AdPublicationAttempt {
            $locked = AdCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $active = $locked->publicationAttempts()->whereIn('status', [
                AdPublicationStatus::Queued, AdPublicationStatus::Validating, AdPublicationStatus::UploadingMedia,
                AdPublicationStatus::CreatingForm, AdPublicationStatus::CreatingCampaign, AdPublicationStatus::CreatingAdSet,
                AdPublicationStatus::CreatingCreative, AdPublicationStatus::CreatingAd,
            ])->exists();
            if ($active) {
                throw ValidationException::withMessages(['publication' => 'A publication is already in progress for this campaign.']);
            }
            if ($retry && ! $locked->latestPublicationAttempt?->retryable) {
                throw ValidationException::withMessages(['publication' => 'This failure cannot be retried safely. Review and edit the campaign first.']);
            }

            $creative = $locked->creative;
            $attempt = AdPublicationAttempt::withoutBusinessScope()->create([
                'business_id' => $locked->business_id,
                'ad_campaign_id' => $locked->id,
                'initiated_by' => $user->id,
                'idempotency_key' => (string) Str::uuid(),
                'status' => AdPublicationStatus::Queued,
                'current_stage' => 'queued',
                'meta_campaign_id' => $locked->meta_campaign_id,
                'meta_adset_id' => $locked->meta_adset_id,
                'meta_creative_id' => $creative?->meta_creative_id,
                'meta_ad_id' => $locked->meta_ad_id,
                'meta_image_hash' => $creative?->meta_image_hash,
                'meta_video_id' => $creative?->meta_video_id,
                'meta_lead_form_id' => $creative?->meta_lead_form_id,
                'request_summary' => ['goal' => $locked->goal->value, 'requested_status' => 'PAUSED'],
            ]);
            $locked->update(['publication_attempt_id' => $attempt->id, 'status' => AdCampaignStatus::Publishing, 'last_error' => null]);

            return $attempt;
        });

        $this->activity->log($campaign, $retry ? 'publication_retried' : 'publication_requested', $attempt, $user);
        PublishMetaCampaign::dispatch($attempt->id, $campaign->id)->afterCommit();

        return $attempt;
    }
}
