<?php

namespace App\Services\Meta;

use App\Models\MetaAdAccount;
use App\Models\MetaBusinessAccount;
use App\Models\MetaConnection;
use App\Models\MetaInstagramAccount;
use App\Models\MetaPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaDisconnectService
{
    public function disconnect(MetaConnection $connection, int $userId): void
    {
        DB::transaction(function () use ($connection): void {
            $connection->update([
                'access_token' => null,
                'status' => MetaConnection::STATUS_REVOKED,
                'last_error' => null,
            ]);

            MetaPage::query()->where('business_id', $connection->business_id)->update([
                'page_access_token' => null,
                'is_selected' => false,
            ]);
            MetaBusinessAccount::query()->where('business_id', $connection->business_id)->update(['is_selected' => false]);
            MetaAdAccount::query()->where('business_id', $connection->business_id)->update(['is_selected' => false]);
            MetaInstagramAccount::query()->where('business_id', $connection->business_id)->update(['is_selected' => false]);
        });

        Log::info('Meta connection disconnected.', [
            'business_id' => $connection->business_id,
            'meta_connection_id' => $connection->id,
            'user_id' => $userId,
        ]);
    }
}
