<?php

namespace App\Services\Meta\Insights;

use App\Models\AdCampaign;

class MetaInsightMapper
{
    public function __construct(private MetaActionMetricResolver $actions) {}

    public function map(AdCampaign $campaign, array $row): array
    {
        $actions = is_array($row['actions'] ?? null) ? $row['actions'] : [];
        $landing = $this->actionListValue($row['actions'] ?? [], 'landing_page_view');
        $inline = $this->nullableInteger($row['inline_link_clicks'] ?? null);
        $result = $this->actions->resolve($campaign->goal, $actions, $landing, $inline);
        $outbound = $this->firstListValue($row['outbound_clicks'] ?? null);

        return [
            'business_id' => $campaign->business_id, 'ad_campaign_id' => $campaign->id,
            'meta_campaign_id' => $campaign->meta_campaign_id, 'insight_date' => $row['date_start'],
            'currency_code' => strtoupper($campaign->metaAdAccount->currency),
            'impressions' => $this->integer($row['impressions'] ?? 0), 'reach' => $this->integer($row['reach'] ?? 0),
            'frequency' => $this->decimal($row['frequency'] ?? null, 4), 'clicks' => $this->integer($row['clicks'] ?? 0),
            'unique_clicks' => $this->nullableInteger($row['unique_clicks'] ?? null), 'inline_link_clicks' => $inline,
            'outbound_clicks' => $outbound, 'landing_page_views' => $landing,
            'leads' => $this->actions->value($actions, ['lead', 'onsite_conversion.lead_grouped', 'offsite_conversion.fb_pixel_lead']),
            'messaging_conversations_started' => $this->actions->value($actions, ['onsite_conversion.messaging_conversation_started_7d', 'messaging_conversation_started_7d', 'onsite_conversion.messaging_first_reply']),
            'purchases' => $this->actions->value($actions, ['purchase', 'omni_purchase', 'offsite_conversion.fb_pixel_purchase']),
            'spend' => $this->decimal($row['spend'] ?? '0', 2) ?? '0.00', 'cpm' => $this->decimal($row['cpm'] ?? null, 4),
            'cpc' => $this->decimal($row['cpc'] ?? null, 4), 'ctr' => $this->decimal($row['ctr'] ?? null, 6),
            'cost_per_result' => $this->actionCost($row['cost_per_action_type'] ?? [], $result['type']),
            'result_type' => $result['type'], 'conversions' => $row['conversions'] ?? null, 'actions' => $actions ?: null,
            'cost_per_action_type' => $row['cost_per_action_type'] ?? null, 'attribution_setting' => $row['attribution_setting'] ?? null,
            'raw_data' => collect($row)->except(['account_id', 'account_name'])->all(), 'synced_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ];
    }

    private function integer(mixed $value): int
    {
        return ctype_digit((string) $value) ? (int) $value : 0;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return $value === null ? null : $this->integer($value);
    }

    private function firstListValue(mixed $list): ?int
    {
        return is_array($list) && isset($list[0]['value']) ? $this->integer($list[0]['value']) : null;
    }

    private function actionListValue(mixed $list, string $type): ?int
    {
        if (! is_array($list)) {
            return null;
        }
        foreach ($list as $item) {
            if (($item['action_type'] ?? null) === $type) {
                return $this->integer($item['value'] ?? 0);
            }
        }

        return null;
    }

    private function actionCost(mixed $list, string $type): ?string
    {
        if (! is_array($list)) {
            return null;
        }
        foreach ($list as $item) {
            if (($item['action_type'] ?? null) === $type) {
                return $this->decimal($item['value'] ?? null, 4);
            }
        }

        return null;
    }

    private function decimal(mixed $value, int $scale): ?string
    {
        if ($value === null || ! preg_match('/^-?\d+(?:\.\d+)?$/', (string) $value)) {
            return null;
        }
        [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '');

        return $whole.'.'.str_pad(substr($fraction, 0, $scale), $scale, '0');
    }
}
