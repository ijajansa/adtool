<?php

namespace App\Notifications;

use App\Models\AdCampaign;
use Illuminate\Notifications\Notification;

class MetaAdvertisementRejected extends Notification
{
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
        return ['event' => 'advertisement_rejected', 'campaign_id' => $this->campaignId, 'message' => "Meta reported an issue with {$this->campaignName}."];
    }
}
