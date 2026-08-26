<?php

namespace App\Models\Concerns;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::creating(function ($model): void {
            if (! $model->business_id && static::canUseBusinessContext()) {
                $model->business_id = Auth::user()->current_business_id;
            }
        });

        static::addGlobalScope('business', function (Builder $builder): void {
            if (! static::canUseBusinessContext()) {
                return;
            }

            $businessId = Auth::user()->current_business_id;

            if ($businessId) {
                $builder->where($builder->qualifyColumn('business_id'), $businessId);
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public static function withoutBusinessScope(): Builder
    {
        return static::query()->withoutGlobalScope('business');
    }

    private static function canUseBusinessContext(): bool
    {
        if (! Auth::check() || ! Auth::user()->current_business_id) {
            return false;
        }

        return ! app()->runningInConsole() || app()->environment('testing');
    }
}
