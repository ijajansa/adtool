<?php

namespace App\Http\Requests\Ads;

use App\Enums\AdCampaignGoal;
use App\Models\AdCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', AdCampaign::class);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'goal' => ['required', Rule::enum(AdCampaignGoal::class)]];
    }
}
