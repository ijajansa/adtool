<?php

namespace Tests\Unit;

use App\Enums\AdCampaignGoal;
use App\Services\Meta\Insights\InsightAggregationService;
use App\Services\Meta\Insights\MetaActionMetricResolver;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MetaInsightServicesTest extends TestCase
{
    public function test_goal_results_choose_one_priority_action_without_double_counting(): void
    {
        $resolver = app(MetaActionMetricResolver::class);
        $actions = [['action_type' => 'lead', 'value' => '3'], ['action_type' => 'onsite_conversion.lead_grouped', 'value' => '3']];
        $this->assertSame(['value' => 3, 'type' => 'lead'], $resolver->resolve(AdCampaignGoal::LeadGeneration, $actions));
        $this->assertSame(['value' => 8, 'type' => 'landing_page_view'], $resolver->resolve(AdCampaignGoal::WebsiteTraffic, [], 8, 10));
    }

    public function test_aggregation_uses_decimal_safe_ratios_and_handles_zero_divisors(): void
    {
        $empty = app(InsightAggregationService::class)->aggregate(new Collection);
        $this->assertSame('0.00', $empty['spend']);
        $this->assertNull($empty['cpc']);
        $this->assertNull($empty['ctr']);
    }
}
