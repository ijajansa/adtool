<?php

namespace App\Jobs;

use App\Exceptions\MetaApiException;
use App\Models\MetaAdAccount;
use App\Services\Meta\Insights\AdAccountSnapshotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAdAccountSnapshot implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 1800;

    public function __construct(public int $adAccountId) {}

    public function uniqueId(): string
    {
        return (string) $this->adAccountId;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(AdAccountSnapshotService $service): void
    {
        $account = MetaAdAccount::withoutBusinessScope()->find($this->adAccountId);
        if (! $account) {
            return;
        }
        try {
            $service->sync($account);
        } catch (MetaApiException $exception) {
            if ($exception->retryable()) {
                throw $exception;
            } report($exception);
        }
    }
}
