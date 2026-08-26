<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BusinessOnboardingService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createFor(User $user, array $attributes): Business
    {
        return DB::transaction(function () use ($user, $attributes): Business {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $existingBusiness = $lockedUser->businesses()
                ->active()
                ->wherePivot('status', true)
                ->first();

            if ($existingBusiness) {
                $lockedUser->forceFill(['current_business_id' => $existingBusiness->id])->save();

                return $existingBusiness;
            }

            $business = Business::create([
                ...Arr::only($attributes, [
                    'name',
                    'industry',
                    'website',
                    'phone',
                    'country_code',
                    'currency_code',
                    'timezone',
                ]),
                'created_by' => $lockedUser->id,
            ]);

            $business->users()->attach($lockedUser->id, [
                'role' => 'owner',
                'status' => true,
                'joined_at' => now(),
            ]);

            $lockedUser->forceFill([
                'current_business_id' => $business->id,
                'timezone' => $business->timezone,
            ])->save();

            return $business;
        });
    }
}
