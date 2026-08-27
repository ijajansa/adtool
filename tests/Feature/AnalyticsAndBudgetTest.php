<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\CampaignInsightDaily;
use App\Models\MetaAdAccount;
use App\Models\MetaPage;
use App\Models\User;
use App\Services\Meta\Insights\MetaInsightsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnalyticsAndBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_insights_follow_pagination_upsert_and_preserve_rows_after_failure(): void
    {
        [$user, $campaign] = $this->campaign();
        $first = $this->insight('2026-08-24', '10.01', '1');
        $second = $this->insight('2026-08-25', '20.00', '2');
        Http::fake(function (Request $request) use ($first, $second) {
            if (($request->data()['after'] ?? null) !== null) {
                return Http::response(['data' => [$second]]);
            }

            return Http::response(['data' => [$first], 'paging' => ['cursors' => ['after' => 'next']]]);
        });
        $service = app(MetaInsightsService::class);
        $this->assertSame(2, $service->sync($campaign, Carbon::parse('2026-08-24'), Carbon::parse('2026-08-25')));
        $this->assertDatabaseCount('campaign_insights_daily', 2);
        $this->assertDatabaseHas('campaign_insights_daily', ['ad_campaign_id' => $campaign->id, 'insight_date' => '2026-08-24', 'spend' => '10.01', 'landing_page_views' => 1]);

        Http::fake(['*' => Http::response(['error' => ['code' => 100]], 400)]);
        try {
            $service->sync($campaign, Carbon::parse('2026-08-24'), Carbon::parse('2026-08-25'));
        } catch (\Throwable) {
        }
        $this->assertDatabaseCount('campaign_insights_daily', 2);
    }

    public function test_repeated_sync_updates_instead_of_duplicating_daily_rows(): void
    {
        [$user, $campaign] = $this->campaign();
        Http::fake(['*' => Http::response(['data' => [$this->insight('2026-08-24', '12.50', '4')]])]);
        $service = app(MetaInsightsService::class);
        $service->sync($campaign, Carbon::parse('2026-08-24'), Carbon::parse('2026-08-24'));
        $service->sync($campaign, Carbon::parse('2026-08-24'), Carbon::parse('2026-08-24'));
        $this->assertDatabaseCount('campaign_insights_daily', 1);
        $this->assertDatabaseHas('campaign_insights_daily', ['spend' => '12.50', 'landing_page_views' => 4]);
    }

    public function test_budget_permissions_success_failure_and_paused_status_safety(): void
    {
        [$owner, $campaign] = $this->campaign();
        $marketer = User::factory()->create();
        $campaign->business->users()->attach($marketer, ['role' => 'marketer', 'status' => true, 'joined_at' => now()]);
        $marketer->update(['current_business_id' => $campaign->business_id]);
        $payload = ['amount' => '25.00', 'confirm' => '1'];
        $this->actingAs($marketer)->withSession(['auth.password_confirmed_at' => time()])->put(route('campaigns.budget.update', $campaign), $payload)->assertForbidden();

        Http::fake(fn (Request $request) => $request->method() === 'GET' ? Http::response(['daily_budget' => '1000', 'configured_status' => 'PAUSED']) : Http::response(['success' => true]));
        $this->actingAs($owner)->withSession(['auth.password_confirmed_at' => time()])->put(route('campaigns.budget.update', $campaign), $payload)->assertRedirect(route('campaigns.analytics', $campaign));
        $this->assertSame('25.00', $campaign->budget->fresh()->amount);
        $this->assertSame('paused', $campaign->fresh()->status->value);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST' && (string) $request['daily_budget'] === '2500' && ! array_key_exists('status', $request->data()));

    }

    public function test_failed_meta_budget_update_preserves_previous_local_budget(): void
    {
        [$owner, $campaign] = $this->campaign();
        Http::fake(fn (Request $request) => $request->method() === 'GET' ? Http::response(['daily_budget' => '1000']) : Http::response(['error' => ['code' => 100]], 400));
        $this->actingAs($owner)->withSession(['auth.password_confirmed_at' => time()])->put(route('campaigns.budget.update', $campaign), ['amount' => '30.00', 'confirm' => '1'])->assertSessionHasErrors('meta');
        $this->assertSame('10.00', $campaign->budget->fresh()->amount);
        $this->assertDatabaseHas('ad_budget_change_logs', ['ad_campaign_id' => $campaign->id, 'status' => 'failed']);
    }

    public function test_backfill_limits_comparison_tenant_and_csv_formula_safety(): void
    {
        Queue::fake();
        [$user, $campaign] = $this->campaign(['name' => '=SUM(A1:A2)']);
        $this->actingAs($user)->post(route('analytics.backfill.store'), ['date_from' => now()->subDays(100)->toDateString(), 'date_to' => now()->toDateString()])->assertSessionHasErrors('date_to');
        $outsider = User::factory()->create();
        $this->createBusinessFor($outsider);
        [, $other] = $this->campaignFor($outsider);
        $this->actingAs($user)->get(route('analytics.compare', ['campaigns' => [$campaign->id, $other->id]]))->assertSessionHasErrors('campaigns');
        CampaignInsightDaily::create(['business_id' => $campaign->business_id, 'ad_campaign_id' => $campaign->id, 'meta_campaign_id' => $campaign->meta_campaign_id, 'insight_date' => now()->toDateString(), 'currency_code' => 'USD', 'spend' => '1.00', 'synced_at' => now()]);
        $response = $this->actingAs($user)->get(route('analytics.export', ['type' => 'daily-insights']))->assertOk();
        $this->assertStringContainsString("'=SUM(A1:A2)", $response->streamedContent());
    }

    private function insight(string $date, string $spend, string $results): array
    {
        return ['campaign_id' => 'cmp_1', 'date_start' => $date, 'date_stop' => $date, 'impressions' => '1000', 'reach' => '800', 'frequency' => '1.25', 'clicks' => '20', 'inline_link_clicks' => '10', 'spend' => $spend, 'cpm' => '10.01', 'cpc' => '0.50', 'ctr' => '2.0', 'actions' => [['action_type' => 'landing_page_view', 'value' => $results]], 'attribution_setting' => '7d_click_1d_view'];
    }

    private function campaign(array $attributes = []): array
    {
        $user = User::factory()->create();

        return $this->campaignFor($user, $attributes);
    }

    private function campaignFor(User $user, array $attributes = []): array
    {
        $business = $user->currentBusiness ?: $this->createBusinessFor($user, attributes: ['currency_code' => 'USD', 'timezone' => 'UTC']);
        $connection = $this->createMetaConnection($business, $user);
        $account = MetaAdAccount::create(['business_id' => $business->id, 'meta_connection_id' => $connection->id, 'meta_ad_account_id' => 'act_'.$business->id, 'name' => 'Account', 'currency' => 'USD', 'timezone_name' => 'UTC', 'account_status' => 1]);
        $page = MetaPage::create(['business_id' => $business->id, 'meta_connection_id' => $connection->id, 'meta_page_id' => 'page_'.$business->id, 'name' => 'Page']);
        $campaign = AdCampaign::create(['business_id' => $business->id, 'created_by' => $user->id, 'meta_connection_id' => $connection->id, 'meta_ad_account_id' => $account->id, 'meta_page_id' => $page->id, 'name' => 'Analytics campaign', 'goal' => 'website_traffic', 'status' => 'paused', 'meta_campaign_id' => 'cmp_'.$business->id, 'meta_adset_id' => 'set_'.$business->id, 'meta_ad_id' => 'ad_'.$business->id, 'configured_status' => 'PAUSED', ...$attributes]);
        $campaign->budget()->create(['business_id' => $business->id, 'budget_type' => 'daily', 'amount' => '10.00', 'currency_code' => 'USD', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10)]);

        return [$user, $campaign];
    }
}
