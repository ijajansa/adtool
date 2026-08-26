<?php

namespace Tests\Feature;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\MetaAdAccount;
use App\Models\MetaPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdCampaignManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_ready_validation_checks_all_sections_and_keeps_incomplete_campaign_draft(): void
    {
        [$user, $campaign] = $this->campaign();
        $campaign->update(['current_step' => 6]);
        $this->actingAs($user)->post(route('campaigns.mark-ready', $campaign))
            ->assertSessionHasErrors(['creative', 'audience', 'budget']);
        $this->assertSame(AdCampaignStatus::Draft, $campaign->fresh()->status);
    }

    public function test_complete_campaign_can_be_marked_ready_without_publishing_to_meta(): void
    {
        [$user, $campaign] = $this->campaign(['current_step' => 6]);
        Storage::disk('local')->put('ads/ready.jpg', 'verified-local-media');
        $campaign->creative()->create([
            'business_id' => $campaign->business_id, 'format' => 'single_image', 'primary_text' => 'Local ad text',
            'call_to_action' => 'LEARN_MORE', 'destination_url' => 'https://example.com', 'media_path' => 'ads/ready.jpg', 'mime_type' => 'image/jpeg',
        ]);
        $campaign->audience()->create([
            'business_id' => $campaign->business_id, 'location_type' => 'country', 'countries' => ['India'],
            'age_min' => 18, 'age_max' => 65, 'genders' => ['all'], 'advantage_audience' => true,
        ]);
        $campaign->budget()->create([
            'business_id' => $campaign->business_id, 'budget_type' => 'daily', 'amount' => 10,
            'currency_code' => 'USD', 'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($user)->post(route('campaigns.mark-ready', $campaign))->assertRedirect(route('campaigns.show', $campaign));
        $campaign->refresh();
        $this->assertSame(AdCampaignStatus::Ready, $campaign->status);
        $this->assertNull($campaign->meta_campaign_id);
    }

    public function test_duplicate_clears_meta_ids_and_copies_private_media(): void
    {
        [$user, $campaign] = $this->campaign();
        Storage::disk('local')->put('ads/source.jpg', 'image-bytes');
        $campaign->creative()->create([
            'business_id' => $campaign->business_id, 'format' => 'single_image', 'primary_text' => 'Text',
            'call_to_action' => 'LEARN_MORE', 'destination_url' => 'https://example.com', 'media_path' => 'ads/source.jpg',
            'mime_type' => 'image/jpeg', 'meta_creative_id' => 'creative-meta',
        ]);
        $campaign->update(['meta_campaign_id' => 'campaign-meta', 'meta_adset_id' => 'set-meta', 'meta_ad_id' => 'ad-meta']);

        $this->actingAs($user)->post(route('campaigns.duplicate', $campaign))->assertRedirect();
        $copy = AdCampaign::where('name', 'Local campaign Copy')->firstOrFail();
        $this->assertSame(AdCampaignStatus::Draft, $copy->status);
        $this->assertNull($copy->meta_campaign_id);
        $this->assertNull($copy->creative->meta_creative_id);
        $this->assertNotSame($campaign->creative->media_path, $copy->creative->media_path);
        Storage::disk('local')->assertExists($copy->creative->media_path);
    }

    public function test_published_campaign_cannot_be_deleted_but_unpublished_draft_can(): void
    {
        [$user, $published] = $this->campaign(['meta_campaign_id' => 'meta-123']);
        $this->actingAs($user)->delete(route('campaigns.destroy', $published))->assertForbidden();
        $this->assertDatabaseHas('ad_campaigns', ['id' => $published->id, 'deleted_at' => null]);

        $draft = AdCampaign::create([
            'business_id' => $published->business_id, 'created_by' => $user->id,
            'name' => 'Delete me', 'goal' => 'website_traffic', 'status' => 'draft',
        ]);
        $this->actingAs($user)->delete(route('campaigns.destroy', $draft))->assertRedirect(route('campaigns.index'));
        $this->assertSoftDeleted($draft);
    }

    public function test_filters_and_pagination_preserve_query_string(): void
    {
        [$user, $campaign] = $this->campaign();
        foreach (range(1, 16) as $number) {
            AdCampaign::create([
                'business_id' => $campaign->business_id, 'created_by' => $user->id,
                'name' => 'Filtered '.$number, 'goal' => 'lead_generation', 'status' => 'ready',
            ]);
        }

        $this->actingAs($user)->get(route('campaigns.index', ['status' => 'ready', 'goal' => 'lead_generation', 'search' => 'Filtered']))
            ->assertOk()->assertSee('Filtered 15')->assertDontSee('Local campaign')->assertSee('status=ready&amp;', false);
    }

    private function campaign(array $attributes = []): array
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user);
        $connection = $this->createMetaConnection($business, $user);
        $ad = MetaAdAccount::create(['business_id' => $business->id, 'meta_connection_id' => $connection->id, 'meta_ad_account_id' => 'act_1', 'name' => 'Ad', 'currency' => 'USD', 'timezone_name' => 'UTC', 'is_selected' => true]);
        $page = MetaPage::create(['business_id' => $business->id, 'meta_connection_id' => $connection->id, 'meta_page_id' => 'page_1', 'name' => 'Page', 'is_selected' => true]);
        $campaign = AdCampaign::create([
            'business_id' => $business->id, 'created_by' => $user->id, 'meta_connection_id' => $connection->id,
            'meta_ad_account_id' => $ad->id, 'meta_page_id' => $page->id,
            'name' => 'Local campaign', 'goal' => 'website_traffic', 'status' => 'draft', ...$attributes,
        ]);

        return [$user, $campaign];
    }
}
