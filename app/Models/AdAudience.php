<?php

namespace App\Models;

use App\Enums\AdLocationType;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdAudience extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'ad_campaign_id', 'location_type', 'countries', 'states', 'cities',
        'latitude', 'longitude', 'radius', 'radius_unit', 'age_min', 'age_max', 'genders',
        'interests', 'advantage_audience', 'raw_targeting',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    protected function casts(): array
    {
        return [
            'location_type' => AdLocationType::class,
            'countries' => 'array', 'states' => 'array', 'cities' => 'array',
            'latitude' => 'decimal:7', 'longitude' => 'decimal:7',
            'age_min' => 'integer', 'age_max' => 'integer', 'radius' => 'integer',
            'genders' => 'array', 'interests' => 'array', 'advantage_audience' => 'boolean',
            'raw_targeting' => 'array',
        ];
    }
}
