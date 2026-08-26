<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => $this->country_code ? strtoupper((string) $this->country_code) : null,
            'currency_code' => strtoupper((string) $this->currency_code),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', Rule::in(config('business.industries'))],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country_code' => ['required', 'string', Rule::in(array_keys(config('business.countries')))],
            'currency_code' => ['required', 'string', Rule::in(array_keys(config('business.currencies')))],
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
        ];
    }
}
