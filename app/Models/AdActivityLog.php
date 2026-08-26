<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdActivityLog extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'user_id', 'ad_campaign_id', 'ad_publication_attempt_id', 'event', 'context'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AdPublicationAttempt::class, 'ad_publication_attempt_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['context' => 'array'];
    }
}
