<?php

namespace App\Jobs;

use App\Models\AdCampaign;
use App\Models\AdPublicationAttempt;
use App\Services\Meta\Publishing\MetaPublishingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class PublishMetaCampaign implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function __construct(public int $attemptId, public int $campaignId) {}

    public function uniqueId(): string
    {
        return 'meta-publish-'.$this->campaignId;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(MetaPublishingService $publishing): void
    {
        $current = DB::transaction(fn () => (int) AdCampaign::withoutBusinessScope()->lockForUpdate()->findOrFail($this->campaignId)->publication_attempt_id === $this->attemptId);
        if (! $current) {
            return;
        }
        $attempt = AdPublicationAttempt::withoutBusinessScope()->findOrFail($this->attemptId);
        $publishing->publish($attempt);
        $attempt->refresh();
        if ($attempt->error_code === 'VIDEO_PROCESSING' && $attempt->retryable) {
            $poll = (int) data_get($attempt->response_summary, 'video_poll_count', 1);
            $delays = config('meta_publishing.video_poll_backoff_seconds');
            ContinueMetaVideoPublication::dispatch($attempt->id, $this->campaignId)->delay(now()->addSeconds($delays[min($poll - 1, count($delays) - 1)]));
        }
    }
}
