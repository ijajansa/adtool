<?php

namespace App\Models;

use App\Enums\AdPublicationStatus;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdPublicationAttempt extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'ad_campaign_id', 'initiated_by', 'idempotency_key', 'status',
        'meta_campaign_id', 'meta_adset_id', 'meta_creative_id', 'meta_ad_id',
        'meta_image_hash', 'meta_video_id', 'meta_lead_form_id', 'current_stage',
        'request_summary', 'response_summary', 'error_code', 'error_subcode',
        'error_type', 'error_message', 'retryable', 'started_at', 'completed_at', 'failed_at',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    protected function casts(): array
    {
        return [
            'status' => AdPublicationStatus::class,
            'request_summary' => 'array',
            'response_summary' => 'array',
            'retryable' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
