<?php

namespace App\Http\Requests\Ads;

use App\Enums\AdLocationType;
use App\Http\Requests\Ads\Concerns\AuthorizesCampaignUpdate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignAudienceRequest extends FormRequest
{
    use AuthorizesCampaignUpdate;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'advantage_audience' => $this->boolean('advantage_audience'),
            'countries' => $this->cleanList($this->input('countries')),
            'states' => $this->cleanList($this->input('states')),
            'cities' => $this->cleanList($this->input('cities')),
            'interests' => collect($this->cleanList($this->input('interests')))->map(fn ($name) => ['name' => $name, 'status' => 'requires_meta_validation'])->values()->all(),
        ]);
    }

    public function rules(): array
    {
        return [
            'location_type' => ['required', Rule::enum(AdLocationType::class)],
            'countries' => ['required_if:location_type,country', 'nullable', 'array'],
            'countries.*' => ['string', 'max:100'],
            'states' => ['required_if:location_type,state', 'nullable', 'array'],
            'states.*' => ['string', 'max:100'],
            'cities' => ['required_if:location_type,city', 'nullable', 'array'],
            'cities.*' => ['string', 'max:100'],
            'latitude' => ['required_if:location_type,radius', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_if:location_type,radius', 'nullable', 'numeric', 'between:-180,180'],
            'radius' => ['required_if:location_type,radius', 'nullable', 'integer', 'between:'.config('ads.radius.min').','.config('ads.radius.max')],
            'radius_unit' => ['required', Rule::in(['kilometer', 'mile'])],
            'age_min' => ['required', 'integer', 'between:18,65'],
            'age_max' => ['required', 'integer', 'between:18,65', 'gte:age_min'],
            'genders' => ['nullable', 'array'],
            'genders.*' => [Rule::in(config('ads.genders'))],
            'interests' => ['nullable', 'array'],
            'interests.*.name' => ['required', 'string', 'max:100'],
            'interests.*.status' => ['required', Rule::in(['requires_meta_validation'])],
            'advantage_audience' => ['boolean'],
        ];
    }

    private function cleanList(mixed $value): array
    {
        $values = is_array($value) ? $value : explode(',', (string) $value);

        return collect($values)->map(fn ($item) => trim((string) $item))->filter()->unique()->values()->all();
    }
}
