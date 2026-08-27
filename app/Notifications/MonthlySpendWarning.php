<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class MonthlySpendWarning extends Notification
{
    public function __construct(private string $period, private string $currency) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['event' => 'monthly_spend_warning', 'period' => $this->period, 'currency' => $this->currency, 'message' => 'The local monthly advertising warning threshold was reached.'];
    }
}
