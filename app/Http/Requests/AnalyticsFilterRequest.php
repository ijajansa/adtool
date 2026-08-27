<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->currentBusiness !== null;
    }

    public function rules(): array
    {
        return ['date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'], 'goal' => ['nullable', 'in:website_traffic,lead_generation,whatsapp_messages'], 'status' => ['nullable', 'in:draft,ready,publishing,active,paused,completed,failed'], 'ad_account' => ['nullable', 'integer'], 'campaigns' => ['nullable', 'array', 'max:5'], 'campaigns.*' => ['integer']];
    }

    public function range(): array
    {
        return [$this->date('date_from')?->startOfDay() ?? now()->subDays(6)->startOfDay(), $this->date('date_to')?->endOfDay() ?? now()->endOfDay()];
    }
}
