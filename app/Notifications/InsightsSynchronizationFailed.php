<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class InsightsSynchronizationFailed extends Notification
{
    public function __construct(private int $campaignId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['event' => 'insights_sync_failed', 'campaign_id' => $this->campaignId, 'message' => 'Meta insights synchronization repeatedly failed.'];
    }
}
