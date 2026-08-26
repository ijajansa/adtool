<?php

namespace App\Models;

use App\Enums\AdBudgetType;
use App\Enums\AdCampaignGoal;
use App\Enums\AdCampaignStatus;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdCampaign extends Model
{
    use BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id', 'created_by', 'meta_connection_id', 'meta_ad_account_id',
        'meta_page_id', 'meta_instagram_account_id', 'name', 'goal', 'status',
        'meta_campaign_id', 'meta_adset_id', 'meta_ad_id', 'current_step',
        'publication_attempt_id', 'effective_status', 'configured_status',
        'published_at', 'last_synced_at', 'starts_at', 'ends_at', 'last_error',
        'special_ad_category_declared', 'special_ad_categories',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function metaConnection(): BelongsTo
    {
        return $this->belongsTo(MetaConnection::class);
    }

    public function metaAdAccount(): BelongsTo
    {
        return $this->belongsTo(MetaAdAccount::class);
    }

    public function metaPage(): BelongsTo
    {
        return $this->belongsTo(MetaPage::class);
    }

    public function metaInstagramAccount(): BelongsTo
    {
        return $this->belongsTo(MetaInstagramAccount::class);
    }

    public function audience(): HasOne
    {
        return $this->hasOne(AdAudience::class);
    }

    public function budget(): HasOne
    {
        return $this->hasOne(AdBudget::class);
    }

    public function creative(): HasOne
    {
        return $this->hasOne(AdCreative::class);
    }

    public function publicationAttempts(): HasMany
    {
        return $this->hasMany(AdPublicationAttempt::class);
    }

    public function latestPublicationAttempt(): HasOne
    {
        return $this->hasOne(AdPublicationAttempt::class)->latestOfMany();
    }

    public function publicationAttempt(): BelongsTo
    {
        return $this->belongsTo(AdPublicationAttempt::class, 'publication_attempt_id');
    }

    public function scopeDrafts(Builder $query): Builder
    {
        return $query->where('status', AdCampaignStatus::Draft);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [AdCampaignStatus::Draft, AdCampaignStatus::Ready, AdCampaignStatus::Failed], true);
    }

    public function hasBeenPublished(): bool
    {
        return $this->meta_campaign_id !== null || $this->published_at !== null;
    }

    public function estimatedTotalBudget(): ?string
    {
        if (! $this->budget) {
            return null;
        }
        if ($this->budget->budget_type === AdBudgetType::Lifetime) {
            return $this->budget->amount;
        }
        if (! $this->budget->ends_at) {
            return null;
        }

        $days = max(1, (int) ceil($this->budget->starts_at->diffInHours($this->budget->ends_at) / 24));

        return number_format((float) $this->budget->amount * $days, 2, '.', '');
    }

    protected function casts(): array
    {
        return [
            'goal' => AdCampaignGoal::class,
            'status' => AdCampaignStatus::class,
            'current_step' => 'integer',
            'published_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'special_ad_category_declared' => 'boolean',
            'special_ad_categories' => 'array',
            'deleted_at' => 'datetime',
        ];
    }
}
