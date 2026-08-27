<?php

namespace App\Console\Commands;

use App\Jobs\SyncAdAccountSnapshot;
use App\Models\MetaAdAccount;
use Illuminate\Console\Command;

class SyncAdAccountSnapshots extends Command
{
    protected $signature = 'meta:sync-account-snapshots';

    protected $description = 'Queue Meta ad-account snapshots';

    public function handle(): int
    {
        MetaAdAccount::withoutBusinessScope()->select('id')->chunkById(100, fn ($accounts) => $accounts->each(fn ($account) => SyncAdAccountSnapshot::dispatch($account->id)));
        $this->info('Account snapshots queued.');

        return self::SUCCESS;
    }
}
