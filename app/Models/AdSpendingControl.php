<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdSpendingControl extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    protected function casts(): array
    {
        return ['maximum_daily_budget' => 'decimal:2', 'maximum_lifetime_budget' => 'decimal:2', 'monthly_warning_amount' => 'decimal:2', 'monthly_hard_limit' => 'decimal:2', 'require_owner_approval_above' => 'decimal:2', 'notifications_enabled' => 'boolean'];
    }
}
