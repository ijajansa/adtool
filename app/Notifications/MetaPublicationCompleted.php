<?php

namespace App\Notifications;

use App\Models\AdCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MetaPublicationCompleted extends Notification
{
    use Queueable;

    public function __construct(private int $campaignId, private string $campaignName) {}

    public static function for(AdCampaign $campaign): self
    {
        return new self($campaign->id, $campaign->name);
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['event' => 'publication_completed', 'campaign_id' => $this->campaignId, 'message' => "{$this->campaignName} was created in Meta and is paused."];
    }
}
