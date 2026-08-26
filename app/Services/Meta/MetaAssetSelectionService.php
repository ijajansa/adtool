<?php

namespace App\Services\Meta;

use App\Models\Business;
use App\Models\MetaAdAccount;
use App\Models\MetaBusinessAccount;
use App\Models\MetaInstagramAccount;
use App\Models\MetaPage;
use Illuminate\Support\Facades\DB;

class MetaAssetSelectionService
{
    /** @param array<string, int|null> $selection */
    public function select(Business $business, array $selection): void
    {
        DB::transaction(function () use ($business, $selection): void {
            $this->selectOne(MetaBusinessAccount::class, $business->id, $selection['meta_business_account_id'] ?? null);
            $this->selectOne(MetaAdAccount::class, $business->id, $selection['meta_ad_account_id']);
            $this->selectOne(MetaPage::class, $business->id, $selection['meta_page_id']);
            $this->selectOne(MetaInstagramAccount::class, $business->id, $selection['meta_instagram_account_id'] ?? null);
        });
    }

    /** @param class-string<MetaBusinessAccount|MetaAdAccount|MetaPage|MetaInstagramAccount> $model */
    private function selectOne(string $model, int $businessId, ?int $selectedId): void
    {
        $model::query()->where('business_id', $businessId)->update(['is_selected' => false]);

        if ($selectedId) {
            $model::query()
                ->where('business_id', $businessId)
                ->whereKey($selectedId)
                ->update(['is_selected' => true]);
        }
    }
}
