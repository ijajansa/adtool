<?php

namespace App\Services\Meta\Publishing;

use App\Enums\AdCampaignGoal;
use App\Enums\AdCampaignStatus;
use App\Enums\AdCreativeFormat;
use App\Enums\AdPublicationStatus;
use App\Exceptions\MetaApiException;
use App\Models\AdCampaign;
use App\Models\AdPublicationAttempt;
use App\Notifications\MetaPublicationCompleted;
use App\Notifications\MetaPublicationFailed;
use App\Services\Ads\AdActivityLogger;
use App\Services\Meta\MetaGraphApiClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class MetaPublishingService
{
    public function __construct(
        private MetaGraphApiClient $client,
        private MetaPublicationValidationService $validation,
        private MetaCampaignPayloadBuilder $campaignPayload,
        private MetaAdSetPayloadBuilder $adSetPayload,
        private MetaCreativePayloadBuilder $creativePayload,
        private MetaAdPayloadBuilder $adPayload,
        private MetaLeadFormPayloadBuilder $leadFormPayload,
        private AdActivityLogger $activity,
    ) {}

    public function publish(AdPublicationAttempt $attempt): void
    {
        $attempt->load('initiatedBy');
        $campaign = AdCampaign::withoutBusinessScope()->with(['business.metaConnection', 'metaConnection', 'metaAdAccount', 'metaPage', 'metaInstagramAccount', 'creative', 'audience', 'budget'])->findOrFail($attempt->ad_campaign_id);

        try {
            if (! $attempt->started_at) {
                $this->activity->log($campaign, 'publication_started', $attempt, $attempt->initiatedBy);
            }
            $this->stage($attempt, AdPublicationStatus::Validating);
            $errors = $this->validation->validate($campaign);
            if ($errors->isNotEmpty()) {
                throw ValidationException::withMessages($errors->getMessages());
            }
            $this->stageCompleted($campaign, $attempt, AdPublicationStatus::Validating);
            $token = $campaign->metaConnection->access_token;

            if (! $this->media($campaign, $attempt, $token)) {
                return;
            }
            if ($campaign->goal === AdCampaignGoal::LeadGeneration && ! $campaign->creative->meta_lead_form_id) {
                $this->leadForm($campaign, $attempt);
            }
            if (! $campaign->meta_campaign_id) {
                $this->campaign($campaign, $attempt, $token);
            }
            if (! $campaign->meta_adset_id) {
                $this->adSet($campaign, $attempt, $token);
            }
            if (! $campaign->creative->meta_creative_id) {
                $this->creative($campaign, $attempt, $token);
            }
            if (! $campaign->meta_ad_id) {
                $this->ad($campaign, $attempt, $token);
            }

            $campaign->update([
                'status' => AdCampaignStatus::Paused,
                'configured_status' => 'PAUSED',
                'effective_status' => 'PAUSED',
                'published_at' => now(),
                'last_synced_at' => now(),
                'last_error' => null,
            ]);
            $attempt->update(['status' => AdPublicationStatus::Completed, 'current_stage' => 'completed', 'completed_at' => now(), 'failed_at' => null, 'retryable' => false, 'error_message' => null]);
            $this->activity->log($campaign, 'publication_completed', $attempt, $attempt->initiatedBy, ['status' => 'PAUSED']);
            $attempt->initiatedBy?->notify(MetaPublicationCompleted::for($campaign));
        } catch (ValidationException $exception) {
            $this->fail($campaign, $attempt, 'Publication validation failed. Review the campaign details.', false, ['type' => 'validation']);
        } catch (MetaApiException $exception) {
            $context = $exception->context();
            $this->fail($campaign, $attempt, $exception->getMessage(), $exception->retryable(), $context);
            if ($exception->retryable()) {
                throw $exception;
            }
        } catch (Throwable $exception) {
            $this->fail($campaign, $attempt, 'The publication could not be completed safely.', false, ['type' => 'internal']);
            report($exception);
        }
    }

    private function media(AdCampaign $campaign, AdPublicationAttempt $attempt, string $token): bool
    {
        $creative = $campaign->creative;
        if ($creative->meta_image_hash || $creative->meta_video_id) {
            return $creative->format !== AdCreativeFormat::SingleVideo || $this->videoReady($campaign, $creative->meta_video_id, $attempt, $token);
        }
        $this->stage($attempt, AdPublicationStatus::UploadingMedia);
        $contents = Storage::disk('local')->get($creative->media_path);
        $account = $campaign->metaAdAccount->meta_ad_account_id;

        if ($creative->format === AdCreativeFormat::SingleImage) {
            $response = $this->client->postMultipart("{$account}/adimages", $token, 'filename', $contents, 'creative.'.pathinfo($creative->media_path, PATHINFO_EXTENSION), $creative->mime_type);
            $firstImage = is_array($response['images'] ?? null) ? reset($response['images']) : null;
            $hash = (is_array($firstImage) ? ($firstImage['hash'] ?? null) : null) ?? $response['hash'] ?? null;
            if (! $hash) {
                throw new MetaApiException('Meta did not return an image hash.', ['reason' => 'invalid_response', 'path' => 'adimages']);
            }
            $creative->update(['meta_image_hash' => $hash]);
            $attempt->update(['meta_image_hash' => $hash, 'response_summary' => ['media' => 'image_uploaded']]);
        } else {
            $response = $this->client->postMultipart("{$account}/advideos", $token, 'source', $contents, 'creative.'.pathinfo($creative->media_path, PATHINFO_EXTENSION), $creative->mime_type);
            $videoId = $response['id'] ?? null;
            if (! $videoId) {
                throw new MetaApiException('Meta did not return a video ID.', ['reason' => 'invalid_response', 'path' => 'advideos']);
            }
            $creative->update(['meta_video_id' => $videoId]);
            $attempt->update(['meta_video_id' => $videoId]);

            return $this->videoReady($campaign, $videoId, $attempt, $token);
        }
        $this->activity->log($campaign, 'publication_media_uploaded', $attempt, $attempt->initiatedBy);
        $this->stageCompleted($campaign, $attempt, AdPublicationStatus::UploadingMedia);

        return true;
    }

    private function videoReady(AdCampaign $campaign, string $videoId, AdPublicationAttempt $attempt, string $token): bool
    {
        $response = $this->client->get($videoId, $token, ['fields' => 'status']);
        $status = strtoupper((string) (data_get($response, 'status.video_status') ?? data_get($response, 'status.processing_phase.status') ?? 'PROCESSING'));
        if (in_array($status, ['READY', 'COMPLETE', 'COMPLETED'], true)) {
            return true;
        }
        if (in_array($status, ['ERROR', 'FAILED'], true)) {
            throw new MetaApiException('Meta could not process the uploaded video.', ['reason' => 'video_processing_failed', 'path' => $videoId]);
        }
        $summary = $attempt->response_summary ?? [];
        $polls = (int) ($summary['video_poll_count'] ?? 0) + 1;
        if ($polls >= (int) config('meta_publishing.video_poll_attempts')) {
            $this->fail($campaign, $attempt, 'Meta did not finish processing the video within the allowed time.', false, ['type' => 'video_processing_timeout']);

            return false;
        }
        $attempt->update([
            'status' => AdPublicationStatus::Partial,
            'current_stage' => 'uploading_media',
            'retryable' => true,
            'error_code' => 'VIDEO_PROCESSING',
            'error_message' => 'Meta is still processing the video.',
            'response_summary' => [...$summary, 'video_poll_count' => $polls, 'video_status' => $status],
        ]);

        return false;
    }

    private function leadForm(AdCampaign $campaign, AdPublicationAttempt $attempt): void
    {
        $this->stage($attempt, AdPublicationStatus::CreatingForm);
        $token = $campaign->metaPage->page_access_token ?: $campaign->metaConnection->access_token;
        $response = $this->client->postFormWithToken($campaign->metaPage->meta_page_id.'/leadgen_forms', $token, $this->leadFormPayload->build($campaign));
        $id = $this->id($response, 'lead form');
        $campaign->creative->update(['meta_lead_form_id' => $id]);
        $attempt->update(['meta_lead_form_id' => $id]);
        $this->activity->log($campaign, 'publication_lead_form_created', $attempt, $attempt->initiatedBy);
        $this->stageCompleted($campaign, $attempt, AdPublicationStatus::CreatingForm);
    }

    private function campaign(AdCampaign $campaign, AdPublicationAttempt $attempt, string $token): void
    {
        $this->stage($attempt, AdPublicationStatus::CreatingCampaign);
        $payload = $this->campaignPayload->build($campaign);
        $id = $this->id($this->client->postFormWithToken($campaign->metaAdAccount->meta_ad_account_id.'/campaigns', $token, $payload), 'campaign');
        $campaign->update(['meta_campaign_id' => $id]);
        $attempt->update(['meta_campaign_id' => $id, 'request_summary' => ['objective' => $payload['objective'], 'status' => 'PAUSED']]);
        $this->activity->log($campaign, 'publication_campaign_created', $attempt, $attempt->initiatedBy, ['status' => 'PAUSED']);
        $this->stageCompleted($campaign, $attempt, AdPublicationStatus::CreatingCampaign);
    }

    private function adSet(AdCampaign $campaign, AdPublicationAttempt $attempt, string $token): void
    {
        $this->stage($attempt, AdPublicationStatus::CreatingAdSet);
        $payload = $this->adSetPayload->build($campaign->fresh(['business', 'metaAdAccount', 'metaPage', 'creative', 'audience', 'budget']));
        $id = $this->id($this->client->postFormWithToken($campaign->metaAdAccount->meta_ad_account_id.'/adsets', $token, $payload), 'ad set');
        $campaign->update(['meta_adset_id' => $id]);
        $attempt->update(['meta_adset_id' => $id]);
        $this->activity->log($campaign, 'publication_adset_created', $attempt, $attempt->initiatedBy, ['status' => 'PAUSED']);
        $this->stageCompleted($campaign, $attempt, AdPublicationStatus::CreatingAdSet);
    }

    private function creative(AdCampaign $campaign, AdPublicationAttempt $attempt, string $token): void
    {
        $this->stage($attempt, AdPublicationStatus::CreatingCreative);
        $campaign = $campaign->fresh(['metaAdAccount', 'metaPage', 'metaInstagramAccount', 'creative']);
        $id = $this->id($this->client->postFormWithToken($campaign->metaAdAccount->meta_ad_account_id.'/adcreatives', $token, $this->creativePayload->build($campaign)), 'creative');
        $campaign->creative->update(['meta_creative_id' => $id]);
        $attempt->update(['meta_creative_id' => $id]);
        $this->activity->log($campaign, 'publication_creative_created', $attempt, $attempt->initiatedBy);
        $this->stageCompleted($campaign, $attempt, AdPublicationStatus::CreatingCreative);
    }

    private function ad(AdCampaign $campaign, AdPublicationAttempt $attempt, string $token): void
    {
        $this->stage($attempt, AdPublicationStatus::CreatingAd);
        $campaign = $campaign->fresh(['metaAdAccount', 'creative']);
        $id = $this->id($this->client->postFormWithToken($campaign->metaAdAccount->meta_ad_account_id.'/ads', $token, $this->adPayload->build($campaign)), 'advertisement');
        $campaign->update(['meta_ad_id' => $id]);
        $attempt->update(['meta_ad_id' => $id]);
        $this->activity->log($campaign, 'publication_ad_created', $attempt, $attempt->initiatedBy, ['status' => 'PAUSED']);
        $this->stageCompleted($campaign, $attempt, AdPublicationStatus::CreatingAd);
    }

    private function stage(AdPublicationAttempt $attempt, AdPublicationStatus $status): void
    {
        $attempt->update(['status' => $status, 'current_stage' => $status->value, 'started_at' => $attempt->started_at ?: now(), 'error_message' => null]);
        $this->activity->log($attempt->campaign, 'publication_stage_started', $attempt, $attempt->initiatedBy, ['stage' => $status->value]);
    }

    private function stageCompleted(AdCampaign $campaign, AdPublicationAttempt $attempt, AdPublicationStatus $status): void
    {
        $this->activity->log($campaign, 'publication_stage_completed', $attempt, $attempt->initiatedBy, ['stage' => $status->value]);
    }

    private function fail(AdCampaign $campaign, AdPublicationAttempt $attempt, string $message, bool $retryable, array $context): void
    {
        $partial = filled($campaign->meta_campaign_id) || filled($campaign->meta_adset_id) || filled($campaign->meta_ad_id) || filled($campaign->creative?->meta_image_hash) || filled($campaign->creative?->meta_video_id);
        $attempt->update([
            'status' => $partial ? AdPublicationStatus::Partial : AdPublicationStatus::Failed,
            'failed_at' => now(), 'retryable' => $retryable,
            'error_code' => $context['meta_code'] ?? null, 'error_subcode' => $context['meta_subcode'] ?? null,
            'error_type' => $context['meta_type'] ?? ($context['type'] ?? null), 'error_message' => $message,
        ]);
        $campaign->update(['status' => AdCampaignStatus::Failed, 'last_error' => $message]);
        $this->activity->log($campaign, 'publication_failed', $attempt, $attempt->initiatedBy, ['stage' => $attempt->current_stage, 'retryable' => $retryable]);
        $attempt->initiatedBy?->notify(MetaPublicationFailed::for($campaign, $retryable));
    }

    private function id(array $response, string $object): string
    {
        if (blank($response['id'] ?? null)) {
            throw new MetaApiException("Meta did not return a {$object} ID.", ['reason' => 'invalid_response']);
        }

        return (string) $response['id'];
    }
}
