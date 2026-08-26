<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaAdAccount extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'meta_connection_id',
        'meta_business_account_id',
        'meta_ad_account_id',
        'account_id',
        'name',
        'currency',
        'timezone_name',
        'timezone_offset_hours_utc',
        'account_status',
        'disable_reason',
        'amount_spent',
        'spend_cap',
        'balance',
        'is_selected',
        'raw_data',
        'last_synced_at',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(MetaConnection::class, 'meta_connection_id');
    }

    public function metaBusinessAccount(): BelongsTo
    {
        return $this->belongsTo(MetaBusinessAccount::class);
    }

    protected function casts(): array
    {
        return [
            'timezone_offset_hours_utc' => 'decimal:2',
            'amount_spent' => 'decimal:2',
            'spend_cap' => 'decimal:2',
            'balance' => 'decimal:2',
            'is_selected' => 'boolean',
            'raw_data' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }
}
