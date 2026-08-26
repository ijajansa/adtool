<?php

namespace Tests\Feature;

use App\Enums\AdCampaignStatus;
use App\Jobs\PublishMetaCampaign;
use App\Models\AdCampaign;
use App\Models\MetaAdAccount;
use App\Models\MetaPage;
use App\Models\User;
use App\Services\Meta\Publishing\MetaAdSetPayloadBuilder;
use App\Services\Meta\Publishing\MetaPublishingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MetaCampaignPublishingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_owner_can_queue_a_preflighted_campaign_but_duplicate_active_attempt_is_blocked(): void
    {
        Queue::fake();
        [$user, $campaign] = $this->campaign();

        $response = $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->post(route('campaigns.publish', $campaign), [
            'confirm_paused' => '1', 'confirm_billing' => '1', 'confirm_meta_terms' => '1',
        ]);

        $response->assertRedirect(route('campaigns.publish.progress', $campaign));
        $this->assertSame(AdCampaignStatus::Publishing, $campaign->fresh()->status);
        $this->assertDatabaseHas('ad_publication_attempts', ['ad_campaign_id' => $campaign->id, 'status' => 'queued']);
        Queue::assertPushed(PublishMetaCampaign::class);

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->post(route('campaigns.publish', $campaign), [
            'confirm_paused' => '1', 'confirm_billing' => '1', 'confirm_meta_terms' => '1',
        ])->assertSessionHasErrors('publication');
    }

    public function test_publication_creates_every_deliverable_paused_and_persists_ids_immediately(): void
    {
        Queue::fake();
        [$user, $campaign] = $this->campaign();
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->post(route('campaigns.publish', $campaign), [
            'confirm_paused' => '1', 'confirm_billing' => '1', 'confirm_meta_terms' => '1',
        ]);
        $attempt = $campaign->fresh()->publicationAttempt;

        Http::fake(function (Request $request) {
            return match (true) {
                str_ends_with($request->url(), '/act_123/adimages') => Http::response(['images' => ['creative.jpg' => ['hash' => 'hash_123']]]),
                str_ends_with($request->url(), '/act_123/campaigns') => Http::response(['id' => 'cmp_123']),
                str_ends_with($request->url(), '/act_123/adsets') => Http::response(['id' => 'set_123']),
                str_ends_with($request->url(), '/act_123/adcreatives') => Http::response(['id' => 'creative_123']),
                str_ends_with($request->url(), '/act_123/ads') => Http::response(['id' => 'ad_123']),
                default => Http::response(['error' => ['code' => 100]], 400),
            };
        });

        app(MetaPublishingService::class)->publish($attempt);

        $campaign->refresh();
        $this->assertSame('cmp_123', $campaign->meta_campaign_id);
        $this->assertSame('set_123', $campaign->meta_adset_id);
        $this->assertSame('ad_123', $campaign->meta_ad_id);
        $this->assertSame(AdCampaignStatus::Paused, $campaign->status);
        $this->assertSame('PAUSED', $campaign->configured_status);
        $this->assertSame('completed', $attempt->fresh()->status->value);
        $this->assertDatabaseHas('ad_creatives', ['ad_campaign_id' => $campaign->id, 'meta_image_hash' => 'hash_123', 'meta_creative_id' => 'creative_123']);
        foreach (['campaigns', 'adsets', 'ads'] as $edge) {
            Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/act_123/'.$edge) && $request['status'] === 'PAUSED');
        }
    }

    public function test_activation_updates_local_state_only_after_meta_accepts_it(): void
    {
        [$user, $campaign] = $this->campaign(['status' => 'paused', 'meta_ad_id' => 'ad_123', 'configured_status' => 'PAUSED']);
        Http::fake(['*/ad_123' => Http::response(['success' => true])]);

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->post(route('campaigns.activate', $campaign), ['confirm' => '1'])->assertRedirect();

        $this->assertSame(AdCampaignStatus::Active, $campaign->fresh()->status);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/ad_123') && $request['status'] === 'ACTIVE');

    }

    public function test_meta_rejection_does_not_change_local_advertisement_status(): void
    {
        [$user, $campaign] = $this->campaign(['status' => 'active', 'meta_ad_id' => 'ad_123', 'configured_status' => 'ACTIVE']);
        Http::fake(['*' => Http::response(['error' => ['code' => 100]], 400)]);

        $this->actingAs($user)->post(route('campaigns.pause', $campaign), ['confirm' => '1'])->assertSessionHasErrors('meta');

        $this->assertSame(AdCampaignStatus::Active, $campaign->fresh()->status);
    }

    public function test_viewer_cannot_publish_or_activate_and_status_endpoint_is_tenant_protected(): void
    {
        [$owner, $campaign] = $this->campaign();
        $viewer = User::factory()->create();
        $campaign->business->users()->attach($viewer, ['role' => 'viewer', 'status' => true, 'joined_at' => now()]);
        $viewer->update(['current_business_id' => $campaign->business_id]);

        $this->actingAs($viewer)->get(route('campaigns.publish.confirm', $campaign))->assertForbidden();
        $this->actingAs($viewer)->withSession(['auth.password_confirmed_at' => time()])->post(route('campaigns.activate', $campaign), ['confirm' => '1'])->assertForbidden();

        $outsider = User::factory()->create();
        $this->createBusinessFor($outsider);
        $this->actingAs($outsider)->getJson(route('campaigns.publish.status', $campaign))->assertNotFound();
    }

    public function test_marketer_cannot_publish_and_another_tenant_cannot_queue_campaign(): void
    {
        [$owner, $campaign] = $this->campaign();
        $marketer = User::factory()->create();
        $campaign->business->users()->attach($marketer, ['role' => 'marketer', 'status' => true, 'joined_at' => now()]);
        $marketer->update(['current_business_id' => $campaign->business_id]);
        $payload = ['confirm_paused' => '1', 'confirm_billing' => '1', 'confirm_meta_terms' => '1'];

        $this->actingAs($marketer)->withSession(['auth.password_confirmed_at' => time()])->post(route('campaigns.publish', $campaign), $payload)->assertForbidden();
        $outsider = User::factory()->create();
        $this->createBusinessFor($outsider);
        $this->actingAs($outsider)->withSession(['auth.password_confirmed_at' => time()])->post(route('campaigns.publish', $campaign), $payload)->assertNotFound();
    }

    public function test_preflight_blocks_missing_permissions_special_declaration_and_unvalidated_targeting(): void
    {
        Queue::fake();
        [$user, $campaign] = $this->campaign(['special_ad_category_declared' => null]);
        $campaign->metaConnection->update(['granted_scopes' => ['ads_management']]);
        $campaign->audience->update(['interests' => [['name' => 'Local placeholder']]]);

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->post(route('campaigns.publish', $campaign), [
            'confirm_paused' => '1', 'confirm_billing' => '1', 'confirm_meta_terms' => '1',
        ])->assertSessionHasErrors(['meta', 'declaration', 'audience']);
        Queue::assertNothingPushed();
    }

    public function test_ad_set_schedule_is_converted_to_the_ad_account_timezone(): void
    {
        [$user, $campaign] = $this->campaign();
        $campaign->metaAdAccount->update(['timezone_name' => 'Asia/Kolkata']);
        $campaign->budget->update(['starts_at' => Carbon::parse('2027-01-01 00:00:00', 'UTC')]);
        $campaign = $campaign->fresh(['metaAdAccount', 'metaPage', 'creative', 'audience', 'budget']);
        $campaign->meta_campaign_id = 'cmp_1';

        $payload = app(MetaAdSetPayloadBuilder::class)->build($campaign);

        $this->assertSame('2027-01-01T05:30:00+05:30', $payload['start_time']);
        $this->assertArrayHasKey('daily_budget', $payload);
        $this->assertArrayNotHasKey('lifetime_budget', $payload);
    }

    private function campaign(array $attributes = []): array
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user, attributes: ['currency_code' => 'USD', 'timezone' => 'UTC']);
        $connection = $this->createMetaConnection($business, $user);
        $account = MetaAdAccount::create([
            'business_id' => $business->id, 'meta_connection_id' => $connection->id, 'meta_ad_account_id' => 'act_123',
            'name' => 'Main account', 'currency' => 'USD', 'timezone_name' => 'UTC', 'account_status' => 1, 'is_selected' => true,
        ]);
        $page = MetaPage::create([
            'business_id' => $business->id, 'meta_connection_id' => $connection->id, 'meta_page_id' => 'page_123',
            'name' => 'Main Page', 'tasks' => ['ADVERTISE'], 'raw_data' => ['leadgen_tos_accepted' => true], 'is_selected' => true,
        ]);
        $campaign = AdCampaign::create([
            'business_id' => $business->id, 'created_by' => $user->id, 'meta_connection_id' => $connection->id,
            'meta_ad_account_id' => $account->id, 'meta_page_id' => $page->id, 'name' => 'Publishable campaign',
            'goal' => 'website_traffic', 'status' => 'ready', 'current_step' => 6,
            'special_ad_category_declared' => false, 'special_ad_categories' => [], ...$attributes,
        ]);
        $path = UploadedFile::fake()->image('creative.jpg', 1200, 628)->store('ads', 'local');
        $campaign->creative()->create([
            'business_id' => $business->id, 'format' => 'single_image', 'primary_text' => 'A useful local service.',
            'headline' => 'Learn more', 'description' => 'Visit our website', 'call_to_action' => 'LEARN_MORE',
            'destination_url' => 'https://example.com/offer', 'media_path' => $path, 'mime_type' => 'image/jpeg',
        ]);
        $campaign->audience()->create([
            'business_id' => $business->id, 'location_type' => 'country', 'countries' => ['IN'], 'age_min' => 21,
            'age_max' => 60, 'genders' => ['all'], 'advantage_audience' => true,
        ]);
        $campaign->budget()->create([
            'business_id' => $business->id, 'budget_type' => 'daily', 'amount' => '10.00', 'currency_code' => 'USD',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(5),
        ]);

        return [$user, $campaign];
    }
}
