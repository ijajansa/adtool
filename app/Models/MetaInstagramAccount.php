<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaInstagramAccount extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'meta_connection_id',
        'meta_page_id',
        'meta_instagram_account_id',
        'username',
        'name',
        'profile_picture_url',
        'followers_count',
        'is_selected',
        'raw_data',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(MetaConnection::class, 'meta_connection_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(MetaPage::class, 'meta_page_id');
    }

    protected function casts(): array
    {
        return ['is_selected' => 'boolean', 'raw_data' => 'array'];
    }
}
