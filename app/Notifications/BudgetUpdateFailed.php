<?php

namespace App\Notifications;

use App\Models\AdCampaign;
use Illuminate\Notifications\Notification;

class BudgetUpdateFailed extends Notification
{
    public function __construct(private int $campaignId, private string $name) {}

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
        return ['event' => 'budget_update_failed', 'campaign_id' => $this->campaignId, 'message' => "Meta rejected the budget update for {$this->name}."];
    }
}
