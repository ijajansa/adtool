<?php

namespace App\Models;

use App\Enums\AdCreativeFormat;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdCreative extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'ad_campaign_id', 'format', 'primary_text', 'headline', 'description',
        'call_to_action', 'destination_url', 'whatsapp_number', 'lead_form_name', 'media_path',
        'original_filename', 'mime_type', 'file_size', 'width', 'height', 'thumbnail_path', 'meta_creative_id',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    protected function casts(): array
    {
        return ['format' => AdCreativeFormat::class, 'file_size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }
}
