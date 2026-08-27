<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdAccountSnapshot extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(MetaAdAccount::class, 'meta_ad_account_id');
    }

    protected function casts(): array
    {
        return ['amount_spent' => 'decimal:2', 'balance' => 'decimal:2', 'spend_cap' => 'decimal:2', 'snapshot_at' => 'datetime', 'raw_data' => 'array'];
    }
}
