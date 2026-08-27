<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdBudgetChangeLog extends Model
{
    use BelongsToBusiness;

    public $timestamps = false;

    protected $guarded = [];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function casts(): array
    {
        return ['old_amount' => 'decimal:2', 'new_amount' => 'decimal:2', 'created_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
