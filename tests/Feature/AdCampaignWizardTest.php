<?php

namespace Tests\Feature;

use App\Enums\AdCampaignGoal;
use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\Business;
use App\Models\MetaAdAccount;
use App\Models\MetaInstagramAccount;
use App\Models\MetaPage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdCampaignWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_owner_admin_and_marketer_can_create_drafts_but_viewer_cannot(): void
    {
        foreach (['owner', 'admin', 'marketer'] as $role) {
            [$user, $business] = $this->workspace($role);
            $this->actingAs($user)->post(route('campaigns.store'), [
                'name' => ucfirst($role).' campaign', 'goal' => 'website_traffic',
            ])->assertRedirect();
            $this->assertDatabaseHas('ad_campaigns', ['business_id' => $business->id, 'created_by' => $user->id, 'status' => 'draft']);
        }

        [$viewer] = $this->workspace('viewer');
        $this->actingAs($viewer)->post(route('campaigns.store'), ['name' => 'No', 'goal' => 'website_traffic'])->assertForbidden();
    }

    public function test_campaign_is_assigned_to_current_business_and_other_tenant_cannot_access_it(): void
    {
        [$owner, $business] = $this->workspace();
        $this->actingAs($owner)->post(route('campaigns.store'), ['name' => 'Owned draft', 'goal' => 'website_traffic']);
        $campaign = AdCampaign::firstOrFail();
        $this->assertSame($business->id, $campaign->business_id);

        [$other] = $this->workspace();
        $this->actingAs($other)->get(route('campaigns.show', $campaign))->assertNotFound();
    }

    public function test_viewer_can_view_but_cannot_edit_and_required_steps_cannot_be_skipped(): void
    {
        [$owner, $business] = $this->workspace();
        $campaign = $this->campaign($owner, $business, ['current_step' => 2]);
        $viewer = User::factory()->create();
        $business->users()->attach($viewer, ['role' => 'viewer', 'status' => true, 'joined_at' => now()]);
        $viewer->update(['current_business_id' => $business->id]);

        $this->actingAs($viewer)->get(route('campaigns.show', $campaign))->assertOk();
        $this->actingAs($viewer)->get(route('campaigns.wizard.goal.edit', $campaign))->assertForbidden();
        $this->actingAs($owner)->get(route('campaigns.wizard.creative.edit', $campaign))
            ->assertRedirect(route('campaigns.wizard.assets.edit', $campaign));
    }

    public function test_assets_must_belong_to_tenant_and_instagram_must_match_page(): void
    {
        [$user, $business, $ad, $page] = $this->workspace();
        $campaign = $this->campaign($user, $business, ['current_step' => 2]);
        [$other, $otherBusiness, $otherAd] = $this->workspace();

        $this->actingAs($user)->put(route('campaigns.wizard.assets.update', $campaign), [
            'meta_ad_account_id' => $otherAd->id, 'meta_page_id' => $page->id,
        ])->assertSessionHasErrors('meta_ad_account_id');

        $secondPage = MetaPage::create(['business_id' => $business->id, 'meta_connection_id' => $business->metaConnection->id, 'meta_page_id' => 'page-2', 'name' => 'Page 2']);
        $instagram = MetaInstagramAccount::create(['business_id' => $business->id, 'meta_connection_id' => $business->metaConnection->id, 'meta_page_id' => $secondPage->id, 'meta_instagram_account_id' => 'ig-2']);
        $this->actingAs($user)->put(route('campaigns.wizard.assets.update', $campaign), [
            'meta_ad_account_id' => $ad->id, 'meta_page_id' => $page->id, 'meta_instagram_account_id' => $instagram->id,
        ])->assertSessionHasErrors('meta_instagram_account_id');
    }

    public function test_goal_specific_creative_validation_rejects_unsafe_urls_and_normalizes_whatsapp(): void
    {
        [$user, $business] = $this->workspace();
        $website = $this->campaign($user, $business, ['current_step' => 3, 'goal' => AdCampaignGoal::WebsiteTraffic]);
        $this->actingAs($user)->put(route('campaigns.wizard.creative.update', $website), $this->creativeData([
            'destination_url' => 'javascript:alert(1)',
        ]))->assertSessionHasErrors('destination_url');

        $whatsapp = $this->campaign($user, $business, ['current_step' => 3, 'goal' => AdCampaignGoal::WhatsAppMessages]);
        $this->actingAs($user)->put(route('campaigns.wizard.creative.update', $whatsapp), $this->creativeData([
            'call_to_action' => 'WHATSAPP_MESSAGE', 'whatsapp_number' => '+91 (98765) 43210',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('+919876543210', $whatsapp->fresh()->creative->whatsapp_number);

        $lead = $this->campaign($user, $business, ['current_step' => 3, 'goal' => AdCampaignGoal::LeadGeneration]);
        $this->actingAs($user)->put(route('campaigns.wizard.creative.update', $lead), $this->creativeData([
            'call_to_action' => 'SIGN_UP',
        ]))->assertSessionHasErrors('lead_form_name');
    }

    public function test_media_mime_size_and_private_preview_authorization_are_enforced(): void
    {
        [$user, $business] = $this->workspace();
        $campaign = $this->campaign($user, $business, ['current_step' => 3]);
        $this->actingAs($user)->put(route('campaigns.wizard.creative.update', $campaign), $this->creativeData([
            'media' => UploadedFile::fake()->createWithContent('fake.jpg', 'not-an-image'),
        ]))->assertSessionHasErrors('media');
        $this->actingAs($user)->put(route('campaigns.wizard.creative.update', $campaign), $this->creativeData([
            'media' => UploadedFile::fake()->create('large.jpg', 10241, 'image/jpeg'),
        ]))->assertSessionHasErrors('media');

        $this->actingAs($user)->put(route('campaigns.wizard.creative.update', $campaign), $this->creativeData())->assertSessionHasNoErrors();
        $path = $campaign->fresh()->creative->media_path;
        Storage::disk('local')->assertExists($path);
        auth()->logout();
        $this->get(route('campaigns.media.show', $campaign))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('campaigns.media.show', $campaign))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        [$other] = $this->workspace();
        $this->actingAs($other)->get(route('campaigns.media.show', $campaign))->assertNotFound();
    }

    public function test_audience_requires_valid_age_location_radius_and_structures_interests(): void
    {
        [$user, $business] = $this->workspace();
        $campaign = $this->campaign($user, $business, ['current_step' => 4]);
        $this->actingAs($user)->put(route('campaigns.wizard.audience.update', $campaign), [
            'location_type' => 'radius', 'age_min' => 17, 'age_max' => 66, 'radius' => 999, 'radius_unit' => 'kilometer',
        ])->assertSessionHasErrors(['latitude', 'longitude', 'radius', 'age_min', 'age_max']);

        $this->actingAs($user)->put(route('campaigns.wizard.audience.update', $campaign), [
            'location_type' => 'country', 'countries' => 'India', 'age_min' => 18, 'age_max' => 50,
            'radius_unit' => 'kilometer', 'genders' => ['all'], 'interests' => 'Coffee, Fitness', 'advantage_audience' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame(['name' => 'Coffee', 'status' => 'requires_meta_validation'], $campaign->fresh()->audience->interests[0]);
    }

    public function test_budget_currency_cannot_be_manipulated_lifetime_requires_end_and_dates_store_in_utc(): void
    {
        CarbonImmutable::setTestNow('2026-08-26 00:00:00 UTC');
        [$user, $business] = $this->workspace(adAttributes: ['currency' => 'INR', 'timezone_name' => 'Asia/Kolkata']);
        $campaign = $this->campaign($user, $business, ['current_step' => 5]);
        $base = ['budget_type' => 'lifetime', 'amount' => '500.00', 'starts_at' => '2026-08-27T12:00'];

        $this->actingAs($user)->put(route('campaigns.wizard.budget.update', $campaign), [...$base, 'currency_code' => 'USD'])
            ->assertSessionHasErrors(['currency_code', 'ends_at']);
        $this->actingAs($user)->put(route('campaigns.wizard.budget.update', $campaign), [...$base, 'ends_at' => '2026-08-30T12:00'])
            ->assertSessionHasNoErrors();

        $budget = $campaign->fresh()->budget;
        $this->assertSame('INR', $budget->currency_code);
        $this->assertSame('2026-08-27 06:30:00', $budget->starts_at->utc()->format('Y-m-d H:i:s'));
        CarbonImmutable::setTestNow();
    }

    private function workspace(string $role = 'owner', array $adAttributes = []): array
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user, $role);
        $connection = $this->createMetaConnection($business, $user);
        $ad = MetaAdAccount::create([
            'business_id' => $business->id, 'meta_connection_id' => $connection->id,
            'meta_ad_account_id' => 'act_'.$business->id, 'name' => 'Ad account '.$business->id,
            'currency' => 'USD', 'timezone_name' => 'UTC', 'is_selected' => true, ...$adAttributes,
        ]);
        $page = MetaPage::create([
            'business_id' => $business->id, 'meta_connection_id' => $connection->id,
            'meta_page_id' => 'page_'.$business->id, 'name' => 'Page '.$business->id, 'is_selected' => true,
        ]);

        return [$user, $business, $ad, $page];
    }

    private function campaign(User $user, Business $business, array $attributes = []): AdCampaign
    {
        return AdCampaign::create([
            'business_id' => $business->id, 'created_by' => $user->id,
            'meta_connection_id' => $business->metaConnection->id,
            'meta_ad_account_id' => $business->selectedMetaAdAccount->id,
            'meta_page_id' => $business->selectedMetaPage->id,
            'name' => 'Test campaign', 'goal' => AdCampaignGoal::WebsiteTraffic,
            'status' => AdCampaignStatus::Draft, ...$attributes,
        ]);
    }

    private function creativeData(array $overrides = []): array
    {
        return [
            'format' => 'single_image', 'primary_text' => 'Try our product', 'headline' => 'A useful headline',
            'description' => 'Description', 'call_to_action' => 'LEARN_MORE', 'destination_url' => 'https://example.com',
            'media' => UploadedFile::fake()->image('creative.jpg', 800, 600), ...$overrides,
        ];
    }
}
