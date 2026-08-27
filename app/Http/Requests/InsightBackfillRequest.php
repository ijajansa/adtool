<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InsightBackfillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageAnalytics', $this->user()->currentBusiness) ?? false;
    }

    public function rules(): array
    {
        return ['date_from' => ['required', 'date', 'before_or_equal:date_to'], 'date_to' => ['required', 'date', 'before_or_equal:today'], 'campaign_id' => ['nullable', 'integer']];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->date('date_from') && $this->date('date_to') && $this->date('date_from')->diffInDays($this->date('date_to')) + 1 > config('ads.insights.backfill_max_days')) {
                $validator->errors()->add('date_to', 'The requested range exceeds the configured backfill maximum.');
            }
        });
    }
}
