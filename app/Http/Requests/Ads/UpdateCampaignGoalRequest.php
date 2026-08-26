<?php

namespace App\Http\Requests\Ads;

use App\Enums\AdCampaignGoal;
use App\Http\Requests\Ads\Concerns\AuthorizesCampaignUpdate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignGoalRequest extends FormRequest
{
    use AuthorizesCampaignUpdate;

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'goal' => ['required', Rule::enum(AdCampaignGoal::class)]];
    }
}
