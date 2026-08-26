<?php

namespace App\Enums;

enum AdCampaignGoal: string
{
    case WebsiteTraffic = 'website_traffic';
    case LeadGeneration = 'lead_generation';
    case WhatsAppMessages = 'whatsapp_messages';

    public function label(): string
    {
        return config("ads.goals.{$this->value}.label", $this->name);
    }
}
