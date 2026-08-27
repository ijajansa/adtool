<?php

namespace App\Services\Meta\Publishing;

use App\Enums\AdCampaignGoal;
use App\Enums\AdCampaignStatus;
use App\Enums\AdCreativeFormat;
use App\Models\AdCampaign;
use App\Models\MetaConnection;
use App\Services\Ads\CampaignValidationService;
use App\Services\Ads\SpendingControlService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

class MetaPublicationValidationService
{
    public function __construct(private CampaignValidationService $draftValidation, private MetaTargetingBuilder $targeting, private MetaBudgetConverter $money, private SpendingControlService $spendingControls) {}

    public function validate(AdCampaign $campaign): MessageBag
    {
        $campaign->load(['business.metaConnection', 'metaConnection', 'metaAdAccount', 'metaPage', 'metaInstagramAccount', 'creative', 'audience', 'budget', 'latestPublicationAttempt']);
        $errors = new MessageBag($this->draftValidation->validate($campaign)->getMessages());
        $connection = $campaign->metaConnection;

        if (! $campaign->business?->status) {
            $errors->add('business', 'The current business is inactive.');
        }
        if (! in_array($campaign->status, [AdCampaignStatus::Ready, AdCampaignStatus::Publishing, AdCampaignStatus::Failed], true)) {
            $errors->add('campaign', 'The campaign must be ready or safely retryable before publishing.');
        }
        if (config('meta.graph_version') !== config('meta_publishing.contract_version')) {
            $errors->add('meta', 'The configured Meta API version does not match the verified publishing contract.');
        }
        if (! $connection || $connection->status !== MetaConnection::STATUS_CONNECTED || ! $connection->access_token || $connection->token_expires_at?->isPast()) {
            $errors->add('meta', 'Reconnect Meta before publishing.');
        }
        $missing = array_diff(config('meta_publishing.required_permissions'), $connection?->granted_scopes ?? []);
        if ($missing) {
            $errors->add('meta', 'Reconnect Meta and grant these required permissions: '.implode(', ', $missing).'.');
        }
        if ((int) $campaign->metaAdAccount?->account_status !== 1) {
            $errors->add('assets', 'The selected Meta ad account is not active.');
        }
        if (! in_array('ADVERTISE', $campaign->metaPage?->tasks ?? [], true)) {
            $errors->add('assets', 'The connected user needs the ADVERTISE task on the selected Facebook Page.');
        }
        if ($campaign->special_ad_category_declared === null) {
            $errors->add('declaration', 'Answer the special-ad-category declaration before publishing.');
        }
        if ($campaign->special_ad_category_declared && blank($campaign->special_ad_categories)) {
            $errors->add('declaration', 'Select the applicable special-ad category.');
        }
        if ($campaign->budget?->starts_at?->lte(now()->addMinutes(config('meta_publishing.minimum_schedule_lead_minutes')))) {
            $errors->add('budget', 'Move the start time farther into the future before publishing.');
        }
        if ($campaign->budget?->ends_at?->isPast()) {
            $errors->add('budget', 'The advertisement schedule has already expired.');
        }

        if ($campaign->creative) {
            $this->validateMedia($campaign, $errors);
            if ($campaign->goal === AdCampaignGoal::LeadGeneration) {
                $this->validateLeadForm($campaign, $errors);
            }
            if ($campaign->goal === AdCampaignGoal::WhatsAppMessages) {
                $this->validateWhatsApp($campaign, $errors);
            }
        }
        try {
            if ($campaign->audience) {
                $this->targeting->build($campaign);
            }
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $errors->add($key, $message);
                }
            }
        }
        try {
            if ($campaign->budget) {
                $this->money->toMinorUnits($campaign->budget->amount, $campaign->budget->currency_code);
                if (auth()->user()) {
                    $this->spendingControls->validate($campaign, $campaign->budget->amount, auth()->user());
                }
            }
        } catch (\InvalidArgumentException $exception) {
            $errors->add('budget', $exception->getMessage());
        }

        return $errors;
    }

    private function validateMedia(AdCampaign $campaign, MessageBag $errors): void
    {
        $creative = $campaign->creative;
        if (! Storage::disk('local')->exists($creative->media_path)) {
            return;
        }
        $path = Storage::disk('local')->path($creative->media_path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $config = config('ads.media.'.$creative->format->value);
        if (! in_array($mime, $config['mimes'], true)) {
            $errors->add('creative', 'The stored media content is no longer an allowed type.');
        }
        if (filesize($path) > $config['max_kb'] * 1024) {
            $errors->add('creative', 'The stored media exceeds the allowed upload size.');
        }
        if ($creative->format === AdCreativeFormat::SingleImage && @getimagesize($path) === false) {
            $errors->add('creative', 'The stored image could not be verified.');
        }
    }

    private function validateLeadForm(AdCampaign $campaign, MessageBag $errors): void
    {
        $creative = $campaign->creative;
        if (! filter_var($creative->privacy_policy_url, FILTER_VALIDATE_URL) || parse_url($creative->privacy_policy_url, PHP_URL_SCHEME) !== 'https') {
            $errors->add('creative', 'Lead ads require an HTTPS privacy-policy URL.');
        }
        if (! $creative->requested_fields || array_diff($creative->requested_fields, ['FULL_NAME', 'EMAIL', 'PHONE'])) {
            $errors->add('creative', 'Choose at least one supported lead form field.');
        }
        if (blank($creative->completion_title) || blank($creative->completion_message) || blank($creative->completion_button_text)) {
            $errors->add('creative', 'Complete the lead form thank-you content.');
        }
        if (! data_get($campaign->metaPage->raw_data, 'leadgen_tos_accepted')) {
            $errors->add('assets', 'Accept Meta Lead Ads Terms for the selected Page before publishing.');
        }
    }

    private function validateWhatsApp(AdCampaign $campaign, MessageBag $errors): void
    {
        $page = $campaign->metaPage;
        if (! data_get($page->raw_data, 'has_whatsapp_number') && ! data_get($page->raw_data, 'has_whatsapp_business_number')) {
            $errors->add('assets', 'Connect a WhatsApp number to the selected Facebook Page before publishing.');
        }
        $pageNumber = preg_replace('/\D/', '', (string) data_get($page->raw_data, 'whatsapp_number'));
        $creativeNumber = preg_replace('/\D/', '', (string) $campaign->creative->whatsapp_number);
        if ($pageNumber && $pageNumber !== $creativeNumber) {
            $errors->add('creative', 'The WhatsApp number must match the number connected to the selected Facebook Page.');
        }
    }
}
