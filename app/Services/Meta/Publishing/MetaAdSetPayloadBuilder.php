<?php

namespace App\Services\Meta\Publishing;

use App\Enums\AdBudgetType;
use App\Models\AdCampaign;

class MetaAdSetPayloadBuilder
{
    public function __construct(private MetaTargetingBuilder $targeting, private MetaBudgetConverter $money) {}

    public function build(AdCampaign $campaign): array
    {
        $mapping = config('meta_publishing.goals.'.$campaign->goal->value);
        $budgetField = $campaign->budget->budget_type === AdBudgetType::Daily ? 'daily_budget' : 'lifetime_budget';
        $payload = [
            'name' => $campaign->name.' - Ad Set',
            'campaign_id' => $campaign->meta_campaign_id,
            'optimization_goal' => $mapping['optimization_goal'],
            'billing_event' => $mapping['billing_event'],
            'destination_type' => $mapping['destination_type'],
            'targeting' => $this->targeting->build($campaign),
            $budgetField => $this->money->toMinorUnits($campaign->budget->amount, $campaign->budget->currency_code),
            'start_time' => $this->metaTime($campaign, $campaign->budget->starts_at),
            'status' => config('meta_publishing.paused_status'),
        ];
        if ($campaign->budget->ends_at) {
            $payload['end_time'] = $this->metaTime($campaign, $campaign->budget->ends_at);
        }
        if ($campaign->goal->value === 'lead_generation') {
            $payload['promoted_object'] = ['page_id' => $campaign->metaPage->meta_page_id];
        }
        if ($campaign->goal->value === 'whatsapp_messages') {
            $payload['promoted_object'] = [
                'page_id' => $campaign->metaPage->meta_page_id,
                'whatsapp_phone_number' => $campaign->creative->whatsapp_number,
            ];
        }

        return $payload;
    }

    private function metaTime(AdCampaign $campaign, $date): string
    {
        return $date->copy()->setTimezone($campaign->metaAdAccount->timezone_name)->format('Y-m-d\TH:i:sP');
    }
}
