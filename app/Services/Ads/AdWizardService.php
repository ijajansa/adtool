<?php

namespace App\Services\Ads;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class AdWizardService
{
    public function __construct(private CreativeMediaService $media) {}

    public function create(User $user, array $data): AdCampaign
    {
        $business = $user->currentBusiness;
        $connection = $business->metaConnection;

        return AdCampaign::create([
            'business_id' => $business->id,
            'created_by' => $user->id,
            'meta_connection_id' => $connection?->id,
            'meta_ad_account_id' => $business->selectedMetaAdAccount?->id,
            'meta_page_id' => $business->selectedMetaPage?->id,
            'meta_instagram_account_id' => $business->selectedMetaInstagramAccount?->id,
            'name' => $data['name'],
            'goal' => $data['goal'],
            'status' => AdCampaignStatus::Draft,
            'current_step' => 2,
        ]);
    }

    public function updateGoal(AdCampaign $campaign, array $data): void
    {
        $campaign->update([...Arr::only($data, ['name', 'goal']), 'status' => AdCampaignStatus::Draft]);
    }

    public function updateAssets(AdCampaign $campaign, array $data): void
    {
        $campaign->update([
            ...Arr::only($data, ['meta_ad_account_id', 'meta_page_id', 'meta_instagram_account_id']),
            'meta_connection_id' => $campaign->business->metaConnection->id,
            'current_step' => max($campaign->current_step, 3),
            'status' => AdCampaignStatus::Draft,
        ]);
    }

    public function updateCreative(AdCampaign $campaign, array $data, ?UploadedFile $upload): void
    {
        $newMedia = null;
        $oldPaths = [];

        try {
            if ($upload) {
                $newMedia = $this->media->store($campaign, $upload, $data['format']);
            }

            DB::transaction(function () use ($campaign, $data, $newMedia, &$oldPaths): void {
                $existing = $campaign->creative;
                if ($existing && $newMedia) {
                    $oldPaths = [$existing->media_path, $existing->thumbnail_path];
                }

                $payload = [
                    ...Arr::only($data, ['format', 'primary_text', 'headline', 'description', 'call_to_action', 'destination_url', 'whatsapp_number', 'lead_form_name']),
                    'destination_url' => $data['destination_url'] ?? null,
                    'whatsapp_number' => $data['whatsapp_number'] ?? null,
                    'lead_form_name' => $data['lead_form_name'] ?? null,
                    ...($newMedia ?? []),
                    'business_id' => $campaign->business_id,
                ];
                $campaign->creative()->updateOrCreate([], $payload);
                $campaign->update(['current_step' => max($campaign->current_step, 4), 'status' => AdCampaignStatus::Draft]);
            });
        } catch (Throwable $exception) {
            if ($newMedia) {
                $this->media->delete($newMedia['media_path'], $newMedia['thumbnail_path']);
            }
            throw $exception;
        }

        if ($newMedia) {
            $this->media->delete(...$oldPaths);
        }
    }

    public function updateAudience(AdCampaign $campaign, array $data): void
    {
        DB::transaction(function () use ($campaign, $data): void {
            $campaign->audience()->updateOrCreate([], [
                ...Arr::only($data, ['location_type', 'countries', 'states', 'cities', 'latitude', 'longitude', 'radius', 'radius_unit', 'age_min', 'age_max', 'genders', 'interests', 'advantage_audience']),
                'business_id' => $campaign->business_id,
            ]);
            $campaign->update(['current_step' => max($campaign->current_step, 5), 'status' => AdCampaignStatus::Draft]);
        });
    }

    public function updateBudget(AdCampaign $campaign, array $data): void
    {
        $timezone = $campaign->metaAdAccount->timezone_name ?: 'UTC';
        $startsAt = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['starts_at'], $timezone)->utc();
        $endsAt = filled($data['ends_at'] ?? null)
            ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['ends_at'], $timezone)->utc()
            : null;

        DB::transaction(function () use ($campaign, $data, $startsAt, $endsAt): void {
            $campaign->budget()->updateOrCreate([], [
                'business_id' => $campaign->business_id,
                'budget_type' => $data['budget_type'],
                'amount' => $data['amount'],
                'currency_code' => strtoupper($campaign->metaAdAccount->currency),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
            $campaign->update(['starts_at' => $startsAt, 'ends_at' => $endsAt, 'current_step' => max($campaign->current_step, 6), 'status' => AdCampaignStatus::Draft]);
        });
    }
}
