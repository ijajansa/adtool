<?php

namespace App\Http\Requests\Ads;

use App\Enums\AdBudgetType;
use App\Http\Requests\Ads\Concerns\AuthorizesCampaignUpdate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Throwable;

class UpdateCampaignBudgetRequest extends FormRequest
{
    use AuthorizesCampaignUpdate;

    public function rules(): array
    {
        $campaign = $this->route('campaign');
        $currency = strtoupper($campaign->metaAdAccount?->currency ?: '');
        $minimum = config("ads.minimum_budget.{$currency}", config('ads.minimum_budget.default'));

        return [
            'budget_type' => ['required', Rule::enum(AdBudgetType::class)],
            'amount' => ['required', 'numeric', 'min:'.$minimum, 'decimal:0,2'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required_if:budget_type,lifetime', 'nullable', 'date_format:Y-m-d\TH:i'],
            'currency_code' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (blank($this->route('campaign')->metaAdAccount?->currency) || blank($this->route('campaign')->metaAdAccount?->timezone_name)) {
                $validator->errors()->add('amount', 'Synchronize the selected Meta ad account currency and timezone before setting a budget.');

                return;
            }
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            try {
                $timezone = $this->route('campaign')->metaAdAccount?->timezone_name ?: 'UTC';
                $start = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $this->string('starts_at')->toString(), $timezone);
                $end = $this->filled('ends_at') ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $this->string('ends_at')->toString(), $timezone) : null;
                if (! $start || $start->utc()->lte(now('UTC'))) {
                    $validator->errors()->add('starts_at', 'The start time must be in the future.');
                }
                if ($end && $end->lte($start)) {
                    $validator->errors()->add('ends_at', 'The end time must be after the start time.');
                }
            } catch (Throwable) {
                $validator->errors()->add('starts_at', 'Enter a valid schedule.');
            }
        }];
    }
}
