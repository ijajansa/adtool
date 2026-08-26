<?php

namespace App\Services\Ads;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class CampaignDuplicationService
{
    public function __construct(private CreativeMediaService $media) {}

    public function duplicate(AdCampaign $source, User $user): AdCampaign
    {
        $source->load(['audience', 'budget', 'creative']);
        $copiedPaths = [];

        try {
            return DB::transaction(function () use ($source, $user, &$copiedPaths): AdCampaign {
                $copy = $source->replicate(['meta_campaign_id', 'meta_adset_id', 'meta_ad_id', 'published_at', 'last_error']);
                $copy->name = $source->name.' Copy';
                $copy->created_by = $user->id;
                $copy->status = AdCampaignStatus::Draft;
                $copy->save();

                if ($source->audience) {
                    $copy->audience()->create([...$source->audience->only($source->audience->getFillable()), 'business_id' => $copy->business_id]);
                }
                if ($source->budget) {
                    $copy->budget()->create([...$source->budget->only($source->budget->getFillable()), 'business_id' => $copy->business_id]);
                }
                if ($source->creative) {
                    $paths = $this->media->copy($copy, $source->creative->media_path, $source->creative->thumbnail_path);
                    $copiedPaths = array_values(array_filter($paths));
                    $copy->creative()->create([
                        ...$source->creative->only($source->creative->getFillable()),
                        ...$paths,
                        'business_id' => $copy->business_id,
                        'meta_creative_id' => null,
                    ]);
                }

                return $copy;
            });
        } catch (Throwable $exception) {
            $this->media->delete(...$copiedPaths);
            throw $exception;
        }
    }
}
