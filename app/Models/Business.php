<?php

namespace App\Models;

use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
