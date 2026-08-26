<?php

namespace App\Services\Ads;

use App\Enums\AdBudgetType;
use App\Enums\AdCampaignGoal;
use App\Enums\AdLocationType;
use App\Models\AdCampaign;
use App\Models\MetaConnection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;

class CampaignValidationService
{
    public function validate(AdCampaign $campaign): MessageBag
    {
        $campaign->load(['business.metaConnection', 'metaAdAccount', 'metaPage', 'metaInstagramAccount', 'creative', 'audience', 'budget']);
        $errors = new MessageBag;
        $connection = $campaign->business->metaConnection;

        if (! $connection || $connection->status !== MetaConnection::STATUS_CONNECTED || $campaign->meta_connection_id !== $connection->id) {
            $errors->add('assets', 'The Meta connection is no longer connected.');
        }
        if (! $campaign->metaAdAccount || $campaign->metaAdAccount->business_id !== $campaign->business_id || $campaign->metaAdAccount->meta_connection_id !== $connection?->id) {
            $errors->add('assets', 'Select an accessible Meta ad account.');
        }
        if (! $campaign->metaPage || $campaign->metaPage->business_id !== $campaign->business_id || $campaign->metaPage->meta_connection_id !== $connection?->id) {
            $errors->add('assets', 'Select an accessible Facebook Page.');
        }
        if ($campaign->metaInstagramAccount && $campaign->metaInstagramAccount->meta_page_id !== $campaign->meta_page_id) {
            $errors->add('assets', 'The Instagram account is not connected to the selected Facebook Page.');
        }
        if (! $campaign->creative || ! Storage::disk('local')->exists($campaign->creative->media_path)) {
            $errors->add('creative', 'Complete the creative and upload media.');
        } else {
            $creative = $campaign->creative;
            if (blank($creative->primary_text) || mb_strlen($creative->primary_text) > 1250) {
                $errors->add('creative', 'The primary text is required and may not exceed 1,250 characters.');
            }
            if (! in_array($creative->call_to_action, config('ads.goals.'.$campaign->goal->value.'.ctas', []), true)) {
                $errors->add('creative', 'Choose a call to action allowed for the campaign goal.');
            }
            if ($campaign->goal === AdCampaignGoal::WebsiteTraffic && ! $this->safeHttpUrl($creative->destination_url)) {
                $errors->add('creative', 'A valid HTTP or HTTPS destination URL is required for website traffic.');
            }
            if ($campaign->goal === AdCampaignGoal::LeadGeneration && blank($creative->lead_form_name)) {
                $errors->add('creative', 'A lead form name is required for lead generation.');
            }
            if ($campaign->goal === AdCampaignGoal::WhatsAppMessages && ! preg_match('/^\+[1-9][0-9]{7,14}$/', (string) $creative->whatsapp_number)) {
                $errors->add('creative', 'A valid country-code WhatsApp number is required for WhatsApp messages.');
            }
        }
        if (! $campaign->audience) {
            $errors->add('audience', 'Complete the audience section.');
        } else {
            $audience = $campaign->audience;
            if ($audience->age_min < 18 || $audience->age_max > 65 || $audience->age_max < $audience->age_min) {
                $errors->add('audience', 'The audience age range is invalid.');
            }
            $hasLocation = match ($audience->location_type) {
                AdLocationType::Country => filled($audience->countries),
                AdLocationType::State => filled($audience->states),
                AdLocationType::City => filled($audience->cities),
                AdLocationType::Radius => $audience->latitude !== null && $audience->longitude !== null
                    && $audience->radius >= config('ads.radius.min') && $audience->radius <= config('ads.radius.max'),
            };
            if (! $hasLocation) {
                $errors->add('audience', 'At least one valid audience location is required.');
            }
        }
        if (! $campaign->budget || $campaign->budget->starts_at->isPast()) {
            $errors->add('budget', 'Complete the budget with a future start time.');
        } else {
            $budget = $campaign->budget;
            $currency = strtoupper($campaign->metaAdAccount?->currency ?: '');
            $minimum = config('ads.minimum_budget.'.$currency, config('ads.minimum_budget.default'));
            if ($budget->currency_code !== $currency || (float) $budget->amount < (float) $minimum) {
                $errors->add('budget', 'The budget currency or amount is no longer valid for the selected ad account.');
            }
            if ($budget->budget_type === AdBudgetType::Lifetime && ! $budget->ends_at) {
                $errors->add('budget', 'A lifetime budget requires an end date.');
            }
            if ($budget->ends_at && $budget->ends_at->lte($budget->starts_at)) {
                $errors->add('budget', 'The budget end time must be after its start time.');
            }
        }

        return $errors;
    }

    private function safeHttpUrl(?string $url): bool
    {
        if (! $url || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
