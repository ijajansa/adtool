<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignInsightDaily extends Model
{
    use BelongsToBusiness;

    protected $table = 'campaign_insights_daily';

    protected $guarded = [];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    protected function casts(): array
    {
        return [
            'insight_date' => 'date', 'impressions' => 'integer', 'reach' => 'integer', 'clicks' => 'integer',
            'unique_clicks' => 'integer', 'inline_link_clicks' => 'integer', 'outbound_clicks' => 'integer',
            'landing_page_views' => 'integer', 'leads' => 'integer', 'messaging_conversations_started' => 'integer',
            'purchases' => 'integer', 'frequency' => 'decimal:4', 'spend' => 'decimal:2', 'cpm' => 'decimal:4',
            'cpc' => 'decimal:4', 'ctr' => 'decimal:6', 'cost_per_result' => 'decimal:4',
            'conversions' => 'array', 'actions' => 'array', 'cost_per_action_type' => 'array', 'raw_data' => 'array', 'synced_at' => 'datetime',
        ];
    }
}
