<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class MetaAccountRestricted extends Notification
{
    public function __construct(private int $adAccountId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['event' => 'meta_account_restricted', 'ad_account_id' => $this->adAccountId, 'message' => 'Meta reports that an advertising account is disabled or restricted.'];
    }
}
