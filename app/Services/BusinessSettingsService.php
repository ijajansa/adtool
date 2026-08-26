<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BusinessSettingsService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Business $business, array $attributes, ?UploadedFile $logo): void
    {
        $previousLogo = $business->logo_path;
        $newLogo = null;

        try {
            if ($logo) {
                $newLogo = $logo->store('business-logos', 'public');
            }

            $business->update([
                ...Arr::only($attributes, [
                    'name',
                    'industry',
                    'website',
                    'phone',
                    'country_code',
                    'currency_code',
                    'timezone',
                ]),
                ...($newLogo ? ['logo_path' => $newLogo] : []),
            ]);
        } catch (Throwable $exception) {
            if ($newLogo) {
                Storage::disk('public')->delete($newLogo);
            }

            throw $exception;
        }

        if ($newLogo && $previousLogo) {
            Storage::disk('public')->delete($previousLogo);
        }
    }
}
