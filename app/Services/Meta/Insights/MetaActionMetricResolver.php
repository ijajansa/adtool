<?php

namespace App\Services\Meta\Insights;

use App\Enums\AdCampaignGoal;

class MetaActionMetricResolver
{
    private const TYPES = [
        'website_traffic' => ['landing_page_view', 'link_click'],
        'lead_generation' => ['lead', 'onsite_conversion.lead_grouped', 'offsite_conversion.fb_pixel_lead'],
        'whatsapp_messages' => ['onsite_conversion.messaging_conversation_started_7d', 'messaging_conversation_started_7d', 'onsite_conversion.messaging_first_reply'],
        'purchase' => ['purchase', 'omni_purchase', 'offsite_conversion.fb_pixel_purchase'],
    ];

    public function resolve(AdCampaignGoal $goal, array $actions, ?int $landingPageViews = null, ?int $linkClicks = null): array
    {
        if ($goal === AdCampaignGoal::WebsiteTraffic && $landingPageViews !== null) {
            return ['value' => $landingPageViews, 'type' => 'landing_page_view'];
        }
        $values = collect($actions)->filter(fn ($item) => is_array($item) && isset($item['action_type']))->keyBy('action_type');
        foreach (self::TYPES[$goal->value] as $type) {
            if ($values->has($type)) {
                return ['value' => $this->integer($values[$type]['value'] ?? 0), 'type' => $type];
            }
        }
        if ($goal === AdCampaignGoal::WebsiteTraffic && $linkClicks !== null) {
            return ['value' => $linkClicks, 'type' => 'inline_link_click'];
        }

        return ['value' => 0, 'type' => self::TYPES[$goal->value][0]];
    }

    public function value(array $actions, array $types): int
    {
        $byType = collect($actions)->filter(fn ($item) => is_array($item))->keyBy('action_type');
        foreach ($types as $type) {
            if ($byType->has($type)) {
                return $this->integer($byType[$type]['value'] ?? 0);
            }
        }

        return 0;
    }

    private function integer(mixed $value): int
    {
        return max(0, filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : 0);
    }
}
