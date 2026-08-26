<?php

namespace App\Notifications;

use App\Models\AdCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MetaPublicationFailed extends Notification
{
    use Queueable;

    public function __construct(private int $campaignId, private string $campaignName, private bool $retryable) {}

    public static function for(AdCampaign $campaign, bool $retryable): self
    {
        return new self($campaign->id, $campaign->name, $retryable);
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['event' => 'publication_failed', 'campaign_id' => $this->campaignId, 'retryable' => $this->retryable, 'message' => "{$this->campaignName} could not be published to Meta."];
    }
}
