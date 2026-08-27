<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateBudget', $this->route('campaign')) ?? false;
    }

    public function rules(): array
    {
        return ['amount' => ['required', 'decimal:0,2', 'gt:0'], 'ends_at' => ['nullable', 'date', 'after:now'], 'confirm' => ['accepted']];
    }
}
