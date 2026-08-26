<?php

namespace App\Models;

use App\Enums\AdCampaignStatus;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'industry',
        'website',
        'phone',
        'country_code',
        'currency_code',
        'timezone',
        'logo_path',
        'status',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Business $business): void {
            if ($business->slug) {
                return;
            }

            $baseSlug = Str::slug($business->name) ?: 'business';
            $slug = $baseSlug;
            $suffix = 2;

            while (static::withTrashed()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $business->slug = $slug;
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('status', true);
    }

    public function metaConnection(): HasOne
    {
        return $this->hasOne(MetaConnection::class);
    }

    public function metaBusinessAccounts(): HasMany
    {
        return $this->hasMany(MetaBusinessAccount::class);
    }

    public function metaAdAccounts(): HasMany
    {
        return $this->hasMany(MetaAdAccount::class);
    }

    public function metaPages(): HasMany
    {
        return $this->hasMany(MetaPage::class);
    }

    public function metaInstagramAccounts(): HasMany
    {
        return $this->hasMany(MetaInstagramAccount::class);
    }

    public function adCampaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }

    public function draftCampaigns(): HasMany
    {
        return $this->adCampaigns()->where('status', AdCampaignStatus::Draft);
    }

    public function adPublicationAttempts(): HasMany
    {
        return $this->hasMany(AdPublicationAttempt::class);
    }

    public function selectedMetaBusinessAccount(): HasOne
    {
        return $this->hasOne(MetaBusinessAccount::class)->where('is_selected', true);
    }

    public function selectedMetaAdAccount(): HasOne
    {
        return $this->hasOne(MetaAdAccount::class)->where('is_selected', true);
    }

    public function selectedMetaPage(): HasOne
    {
        return $this->hasOne(MetaPage::class)->where('is_selected', true);
    }

    public function selectedMetaInstagramAccount(): HasOne
    {
        return $this->hasOne(MetaInstagramAccount::class)->where('is_selected', true);
    }

    public function hasCompletedMetaSetup(): bool
    {
        return $this->metaConnection?->status === MetaConnection::STATUS_CONNECTED
            && $this->selectedMetaAdAccount !== null
            && $this->selectedMetaPage !== null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('status'), true);
    }

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }
}
