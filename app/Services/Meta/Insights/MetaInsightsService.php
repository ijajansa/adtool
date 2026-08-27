<?php

namespace App\Services\Meta\Insights;

use App\Models\AdCampaign;
use App\Models\CampaignInsightDaily;
use App\Models\CampaignInsightSummary;
use App\Services\Meta\MetaGraphApiClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MetaInsightsService
{
    public const FIELDS = ['campaign_id', 'date_start', 'date_stop', 'impressions', 'reach', 'frequency', 'clicks', 'unique_clicks', 'inline_link_clicks', 'outbound_clicks', 'spend', 'cpm', 'cpc', 'ctr', 'actions', 'conversions', 'cost_per_action_type', 'attribution_setting'];

    public function __construct(private MetaGraphApiClient $client, private MetaInsightMapper $mapper, private InsightAggregationService $aggregation) {}

    public function sync(AdCampaign $campaign, Carbon $from, Carbon $to): int
    {
        $campaign->loadMissing(['metaConnection', 'metaAdAccount']);
        if (! $campaign->meta_campaign_id || ! $campaign->metaConnection?->access_token) {
            return 0;
        }
        $rows = $this->client->getAll($campaign->meta_campaign_id.'/insights', $campaign->metaConnection->access_token, [
            'fields' => implode(',', self::FIELDS), 'level' => 'campaign', 'time_increment' => 1, 'limit' => 100,
            'time_range' => json_encode(['since' => $from->toDateString(), 'until' => $to->toDateString()], JSON_THROW_ON_ERROR),
            'use_unified_attribution_setting' => 'true',
        ]);
        $mapped = collect($rows)->filter(fn ($row) => isset($row['date_start']))->map(fn ($row) => $this->mapper->map($campaign, $row))->all();
        foreach ($mapped as &$item) {
            foreach (['conversions', 'actions', 'cost_per_action_type', 'raw_data'] as $jsonColumn) {
                if (is_array($item[$jsonColumn] ?? null)) {
                    $item[$jsonColumn] = json_encode($item[$jsonColumn], JSON_THROW_ON_ERROR);
                }
            }
        }
        unset($item);

        DB::transaction(function () use ($campaign, $mapped, $from, $to): void {
            if ($mapped) {
                $updates = array_keys(collect($mapped[0])->except(['business_id', 'ad_campaign_id', 'insight_date', 'created_at'])->all());
                CampaignInsightDaily::withoutBusinessScope()->upsert($mapped, ['business_id', 'ad_campaign_id', 'insight_date'], $updates);
            }
            $daily = CampaignInsightDaily::withoutBusinessScope()->with('campaign')->where('ad_campaign_id', $campaign->id)->whereBetween('insight_date', [$from->toDateString(), $to->toDateString()])->get();
            $totals = $this->aggregation->aggregate($daily);
            $types = $daily->pluck('result_type')->filter()->unique()->values();
            $summary = [
                'business_id' => $campaign->business_id, 'ad_campaign_id' => $campaign->id,
                'date_from' => $from->toDateString(), 'date_to' => $to->toDateString(),
                ...collect($totals)->except('reach_is_derived')->all(), 'currency_code' => $campaign->metaAdAccount->currency,
                'result_type' => $types->count() === 1 ? $types->first() : null, 'calculated_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ];
            CampaignInsightSummary::withoutBusinessScope()->upsert([$summary], ['business_id', 'ad_campaign_id', 'date_from', 'date_to'], array_keys(collect($summary)->except(['business_id', 'ad_campaign_id', 'date_from', 'date_to', 'created_at'])->all()));
        });

        return count($mapped);
    }
}
