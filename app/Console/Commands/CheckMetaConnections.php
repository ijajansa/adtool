<?php

namespace App\Console\Commands;

use App\Models\MetaConnection;
use Illuminate\Console\Command;

class CheckMetaConnections extends Command
{
    protected $signature = 'meta:check-connections';

    protected $description = 'Mark expired Meta connections and report connections approaching expiry';

    public function handle(): int
    {
        $now = now();
        $warningDate = $now->copy()->addDays(config('meta.expiry_warning_days'));
        $expired = MetaConnection::withoutBusinessScope()
            ->where('status', MetaConnection::STATUS_CONNECTED)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', $now)
            ->update(['status' => MetaConnection::STATUS_EXPIRED]);

        $expiringSoon = MetaConnection::withoutBusinessScope()
            ->where('status', MetaConnection::STATUS_CONNECTED)
            ->whereBetween('token_expires_at', [$now, $warningDate])
            ->count();

        $this->info("Expired connections marked: {$expired}");
        $this->info("Connected records expiring soon: {$expiringSoon}");

        return self::SUCCESS;
    }
}
