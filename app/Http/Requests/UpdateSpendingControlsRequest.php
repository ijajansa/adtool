<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpendingControlsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageAnalytics', $this->user()->currentBusiness) ?? false;
    }

    public function rules(): array
    {
        return ['maximum_daily_budget' => ['nullable', 'decimal:0,2', 'gt:0'], 'maximum_lifetime_budget' => ['nullable', 'decimal:0,2', 'gt:0'], 'monthly_warning_amount' => ['nullable', 'decimal:0,2', 'gt:0'], 'monthly_hard_limit' => ['nullable', 'decimal:0,2', 'gt:0'], 'require_owner_approval_above' => ['nullable', 'decimal:0,2', 'gt:0'], 'notifications_enabled' => ['nullable', 'boolean']];
    }
}
