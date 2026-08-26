<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaConnectionLog extends Model
{
    use BelongsToBusiness;

    public const UPDATED_AT = null;

    protected $fillable = [
        'business_id',
        'meta_connection_id',
        'user_id',
        'action',
        'status',
        'message',
        'context',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(MetaConnection::class, 'meta_connection_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['context' => 'array', 'created_at' => 'datetime'];
    }
}
