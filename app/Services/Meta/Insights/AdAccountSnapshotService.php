<?php

namespace App\Services\Meta\Insights;

use App\Models\AdAccountSnapshot;
use App\Models\MetaAdAccount;
use App\Services\Meta\MetaAssetSyncService;
use App\Services\Meta\MetaGraphApiClient;

class AdAccountSnapshotService
{
    public function __construct(private MetaGraphApiClient $client, private MetaAssetSyncService $money) {}

    public function sync(MetaAdAccount $account): AdAccountSnapshot
    {
        $account->loadMissing('connection');
        $data = $this->client->get($account->meta_ad_account_id, $account->connection->access_token, ['fields' => 'id,currency,account_status,amount_spent,balance,spend_cap,disable_reason']);
        $currency = strtoupper($data['currency'] ?? $account->currency);

        return AdAccountSnapshot::withoutBusinessScope()->create([
            'business_id' => $account->business_id, 'meta_ad_account_id' => $account->id, 'currency_code' => $currency,
            'account_status' => isset($data['account_status']) ? (string) $data['account_status'] : null,
            'amount_spent' => $this->money->minorUnitsToMajor($data['amount_spent'] ?? null, $currency),
            'balance' => $this->money->minorUnitsToMajor($data['balance'] ?? null, $currency),
            'spend_cap' => $this->money->minorUnitsToMajor($data['spend_cap'] ?? null, $currency),
            'funding_source_status' => $data['funding_source_status'] ?? null, 'disable_reason' => $data['disable_reason'] ?? null,
            'snapshot_at' => now(), 'raw_data' => collect($data)->only(['id', 'currency', 'account_status', 'amount_spent', 'balance', 'spend_cap', 'disable_reason'])->all(),
        ]);
    }
}
