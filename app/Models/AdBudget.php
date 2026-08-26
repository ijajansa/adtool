<?php

namespace App\Models;

use App\Enums\AdBudgetType;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdBudget extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'ad_campaign_id', 'budget_type', 'amount', 'currency_code', 'starts_at', 'ends_at'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    protected function casts(): array
    {
        return ['budget_type' => AdBudgetType::class, 'amount' => 'decimal:2', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }
}
