<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignInsightSummary extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    protected function casts(): array
    {
        return ['date_from' => 'date', 'date_to' => 'date', 'impressions' => 'integer', 'reach' => 'integer', 'clicks' => 'integer', 'results' => 'integer', 'spend' => 'decimal:2', 'cpm' => 'decimal:4', 'cpc' => 'decimal:4', 'ctr' => 'decimal:6', 'cost_per_result' => 'decimal:4', 'calculated_at' => 'datetime'];
    }
}
