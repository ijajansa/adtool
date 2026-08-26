<?php

namespace App\Enums;

enum AdCampaignStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Publishing = 'publishing';
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
