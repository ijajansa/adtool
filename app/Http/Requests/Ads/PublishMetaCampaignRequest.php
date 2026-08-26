<?php

namespace App\Http\Requests\Ads;

use Illuminate\Foundation\Http\FormRequest;

class PublishMetaCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('publish', $this->route('campaign')) ?? false;
    }

    public function rules(): array
    {
        return [
            'confirm_paused' => ['accepted'],
            'confirm_billing' => ['accepted'],
            'confirm_meta_terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return ['*.accepted' => 'Please acknowledge every publication confirmation.'];
    }
}
