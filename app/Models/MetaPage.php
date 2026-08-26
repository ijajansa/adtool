<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaPage extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'meta_connection_id',
        'meta_page_id',
        'name',
        'category',
        'page_access_token',
        'tasks',
        'picture_url',
        'is_selected',
        'raw_data',
    ];

    protected $hidden = ['page_access_token'];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(MetaConnection::class, 'meta_connection_id');
    }

    public function instagramAccounts(): HasMany
    {
        return $this->hasMany(MetaInstagramAccount::class);
    }

    protected function casts(): array
    {
        return [
            'page_access_token' => 'encrypted',
            'tasks' => 'array',
            'is_selected' => 'boolean',
            'raw_data' => 'array',
        ];
    }
}
