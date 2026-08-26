<?php

namespace App\Http\Requests\Ads;

use App\Enums\AdCampaignGoal;
use App\Enums\AdCreativeFormat;
use App\Http\Requests\Ads\Concerns\AuthorizesCampaignUpdate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class UpdateCampaignCreativeRequest extends FormRequest
{
    use AuthorizesCampaignUpdate;

    protected function prepareForValidation(): void
    {
        if ($this->filled('whatsapp_number')) {
            $this->merge(['whatsapp_number' => preg_replace('/[\s()\-]/', '', (string) $this->input('whatsapp_number'))]);
        }
    }

    public function rules(): array
    {
        $campaign = $this->route('campaign');
        $goal = $campaign->goal instanceof AdCampaignGoal ? $campaign->goal->value : $campaign->goal;
        $format = $this->input('format', 'single_image');
        $mediaConfig = config("ads.media.{$format}", config('ads.media.single_image'));
        $mediaRules = ['nullable', File::types($mediaConfig['extensions'])->max($mediaConfig['max_kb'])];
        if (! $campaign->creative) {
            $mediaRules[0] = 'required';
        }

        return [
            'format' => ['required', Rule::enum(AdCreativeFormat::class)],
            'primary_text' => ['required', 'string', 'max:1250'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'call_to_action' => ['required', Rule::in(config("ads.goals.{$goal}.ctas", []))],
            'destination_url' => [$goal === AdCampaignGoal::WebsiteTraffic->value ? 'required' : 'nullable', 'url:http,https', 'max:2048'],
            'whatsapp_number' => [$goal === AdCampaignGoal::WhatsAppMessages->value ? 'required' : 'nullable', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'lead_form_name' => [$goal === AdCampaignGoal::LeadGeneration->value ? 'required' : 'nullable', 'string', 'max:255'],
            'privacy_policy_url' => [$goal === AdCampaignGoal::LeadGeneration->value ? 'required' : 'nullable', 'url:https', 'max:2048'],
            'privacy_policy_link_text' => [$goal === AdCampaignGoal::LeadGeneration->value ? 'required' : 'nullable', 'string', 'max:255'],
            'requested_fields' => [$goal === AdCampaignGoal::LeadGeneration->value ? 'required' : 'nullable', 'array', 'min:1'],
            'requested_fields.*' => [Rule::in(['FULL_NAME', 'EMAIL', 'PHONE'])],
            'completion_title' => [$goal === AdCampaignGoal::LeadGeneration->value ? 'required' : 'nullable', 'string', 'max:255'],
            'completion_message' => [$goal === AdCampaignGoal::LeadGeneration->value ? 'required' : 'nullable', 'string', 'max:1000'],
            'completion_button_text' => [$goal === AdCampaignGoal::LeadGeneration->value ? 'required' : 'nullable', 'string', 'max:255'],
            'completion_destination_url' => ['nullable', 'url:http,https', 'max:2048'],
            'media' => $mediaRules,
        ];
    }

    public function messages(): array
    {
        return ['whatsapp_number.regex' => 'Enter a WhatsApp number with its country code, for example +919876543210.'];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $upload = $this->file('media');
            if (! $upload || $validator->errors()->has('media')) {
                return;
            }

            $allowedMimes = config('ads.media.'.$this->input('format').'.mimes', []);
            if (! in_array($upload->getMimeType(), $allowedMimes, true)) {
                $validator->errors()->add('media', 'The uploaded file content does not match an allowed media type.');
            } elseif ($this->input('format') === 'single_image' && @getimagesize($upload->getRealPath()) === false) {
                $validator->errors()->add('media', 'The uploaded image could not be verified.');
            }
        }];
    }
}
