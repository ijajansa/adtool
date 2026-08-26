<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetaConnection extends Model
{
    use BelongsToBusiness, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'business_id',
        'connected_by',
        'meta_user_id',
        'meta_user_name',
        'access_token',
        'token_type',
        'token_expires_at',
        'granted_scopes',
        'declined_scopes',
        'status',
        'last_synced_at',
        'last_error',
    ];

    protected $hidden = ['access_token'];

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function businessAccounts(): HasMany
    {
        return $this->hasMany(MetaBusinessAccount::class);
    }

    public function adAccounts(): HasMany
    {
        return $this->hasMany(MetaAdAccount::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(MetaPage::class);
    }

    public function instagramAccounts(): HasMany
    {
        return $this->hasMany(MetaInstagramAccount::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MetaConnectionLog::class);
    }

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'granted_scopes' => 'array',
            'declined_scopes' => 'array',
            'last_synced_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
