<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CampaignNoResultsWarning extends Notification
{
    public function __construct(private int $campaignId, private string $period) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['event' => 'campaign_no_results', 'campaign_id' => $this->campaignId, 'period' => $this->period, 'message' => 'A campaign spent money but recorded no result.'];
    }
}
