<?php

namespace App\Services\Ads;

use App\Enums\AdBudgetType;
use App\Models\AdCampaign;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SpendingControlService
{
    public function validate(AdCampaign $campaign, string $amount, User $user): void
    {
        $campaign->loadMissing(['budget', 'business.spendingControl']);
        $control = $campaign->business->spendingControl;
        if (! $control) {
            return;
        }
        if ($control->currency_code !== $campaign->budget->currency_code) {
            throw ValidationException::withMessages(['amount' => 'The spending-control currency does not match this campaign.']);
        }
        $limit = $campaign->budget->budget_type === AdBudgetType::Daily ? $control->maximum_daily_budget : $control->maximum_lifetime_budget;
        if ($limit !== null && $this->compare($amount, $limit) > 0) {
            throw ValidationException::withMessages(['amount' => 'The proposed budget exceeds the business safety limit.']);
        }
        if ($control->require_owner_approval_above !== null && $this->compare($amount, $control->require_owner_approval_above) > 0 && ! $user->hasBusinessRole($campaign->business, 'owner')) {
            throw ValidationException::withMessages(['amount' => 'An owner must make budget changes above the approval threshold.']);
        }
    }

    private function compare(string $left, string $right): int
    {
        $normalize = function (string $value): string {
            [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

            return str_pad((ltrim($whole, '0') ?: '0').str_pad(substr($fraction, 0, 2), 2, '0'), 20, '0', STR_PAD_LEFT);
        };

        return strcmp($normalize($left), $normalize($right));
    }
}
