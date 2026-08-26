<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaBusinessAccount extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'meta_connection_id',
        'meta_business_id',
        'name',
        'verification_status',
        'is_selected',
        'raw_data',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(MetaConnection::class, 'meta_connection_id');
    }

    public function adAccounts(): HasMany
    {
        return $this->hasMany(MetaAdAccount::class);
    }

    protected function casts(): array
    {
        return ['is_selected' => 'boolean', 'raw_data' => 'array'];
    }
}
