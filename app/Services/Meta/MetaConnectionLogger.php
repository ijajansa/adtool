<?php

namespace App\Services\Meta;

use App\Models\Business;
use App\Models\MetaConnection;
use App\Models\MetaConnectionLog;
use App\Models\User;
use Illuminate\Support\Arr;

class MetaConnectionLogger
{
    /**
     * Only explicitly safe operational context keys may be persisted.
     *
     * @param  array<string, mixed>  $context
     */
    public function log(
        Business $business,
        string $action,
        string $status,
        ?string $message = null,
        array $context = [],
        ?MetaConnection $connection = null,
        ?User $user = null,
    ): MetaConnectionLog {
        return MetaConnectionLog::create([
            'business_id' => $business->id,
            'meta_connection_id' => $connection?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'context' => Arr::only($context, [
                'reason',
                'path',
                'http_status',
                'meta_code',
                'meta_subcode',
                'meta_type',
                'asset_counts',
            ]),
        ]);
    }
}
