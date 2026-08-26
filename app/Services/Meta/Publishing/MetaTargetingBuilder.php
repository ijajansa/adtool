<?php

namespace App\Services\Meta\Publishing;

use App\Enums\AdLocationType;
use App\Models\AdCampaign;
use Illuminate\Validation\ValidationException;

class MetaTargetingBuilder
{
    public function build(AdCampaign $campaign): array
    {
        $audience = $campaign->audience;
        $special = $campaign->special_ad_category_declared && filled($campaign->special_ad_categories);
        $targeting = ['geo_locations' => $this->locations($audience->location_type, $audience)];

        if (! $special) {
            $targeting['age_min'] = $audience->age_min;
            $targeting['age_max'] = $audience->age_max;
            $genders = collect($audience->genders)->reject(fn ($gender) => $gender === 'all')->map(fn ($gender) => $gender === 'male' ? 1 : 2)->values()->all();
            if ($genders) {
                $targeting['genders'] = $genders;
            }

            if ($audience->interests) {
                $invalid = collect($audience->interests)->contains(fn ($interest) => ! is_array($interest) || blank($interest['id'] ?? null) || ($interest['status'] ?? null) !== 'validated');
                if ($invalid) {
                    throw ValidationException::withMessages(['audience' => 'Remove or resolve unvalidated interest placeholders before publishing.']);
                }
                $targeting['interests'] = collect($audience->interests)->map(fn ($interest) => ['id' => (string) $interest['id'], 'name' => $interest['name'] ?? null])->all();
            }
        }

        if ($audience->advantage_audience && ! $special) {
            $targeting['targeting_automation'] = ['advantage_audience' => 1];
        }
        if ($campaign->goal->value === 'whatsapp_messages') {
            $targeting['is_whatsapp_destination_ad'] = true;
        }

        return $targeting;
    }

    private function locations(AdLocationType $type, $audience): array
    {
        return match ($type) {
            AdLocationType::Country => ['countries' => $this->countries($audience->countries)],
            AdLocationType::State => ['regions' => $this->identifiedLocations($audience->states, 'state')],
            AdLocationType::City => ['cities' => $this->identifiedLocations($audience->cities, 'city')],
            AdLocationType::Radius => ['custom_locations' => [[
                'latitude' => (string) $audience->latitude,
                'longitude' => (string) $audience->longitude,
                'radius' => $audience->radius,
                'distance_unit' => $audience->radius_unit === 'mile' ? 'mile' : 'kilometer',
            ]]],
        };
    }

    private function countries(?array $countries): array
    {
        $countries = array_values(array_unique(array_map('strtoupper', $countries ?? [])));
        if (! $countries || collect($countries)->contains(fn ($country) => ! preg_match('/^[A-Z]{2}$/', $country))) {
            throw ValidationException::withMessages(['audience' => 'Country targeting must use valid two-letter country codes such as IN or US.']);
        }

        return $countries;
    }

    private function identifiedLocations(?array $locations, string $label): array
    {
        if (! $locations || collect($locations)->contains(fn ($location) => ! is_array($location) || blank($location['key'] ?? null))) {
            throw ValidationException::withMessages(['audience' => "Every {$label} must have a validated Meta location key before publishing."]);
        }

        return collect($locations)->map(fn ($location) => ['key' => (string) $location['key']])->all();
    }
}
