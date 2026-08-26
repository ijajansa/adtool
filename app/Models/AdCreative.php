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
        'meta_image_hash', 'meta_video_id', 'meta_lead_form_id', 'privacy_policy_url',
        'privacy_policy_link_text', 'requested_fields', 'completion_title', 'completion_message',
        'completion_button_text', 'completion_destination_url',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    protected function casts(): array
    {
        return ['format' => AdCreativeFormat::class, 'file_size' => 'integer', 'width' => 'integer', 'height' => 'integer', 'requested_fields' => 'array'];
    }
}
