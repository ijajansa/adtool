<?php

namespace Tests\Feature;

use App\Models\MetaAdAccount;
use App\Models\MetaBusinessAccount;
use App\Models\MetaConnection;
use App\Models\MetaInstagramAccount;
use App\Models\MetaPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaAuthorizationAndSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set([
            'meta.app_id' => 'app-id',
            'meta.redirect_uri' => 'https://app.example.com/meta-connection/callback',
        ]);
    }

    public function test_owner_and_admin_can_connect_but_marketer_and_viewer_cannot(): void
    {
        foreach (['owner' => 302, 'admin' => 302, 'marketer' => 403, 'viewer' => 403] as $role => $status) {
            $user = User::factory()->create();
            $this->createBusinessFor($user, $role);

            $this->actingAs($user)->get(route('meta-connection.redirect'))->assertStatus($status);
        }
    }

    public function test_one_tenant_cannot_see_another_tenants_meta_connection(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user);
        $otherUser = User::factory()->create();
        $otherBusiness = $this->createBusinessFor($otherUser);
        $otherConnection = $this->createMetaConnection($otherBusiness, $otherUser, ['meta_user_name' => 'Other Customer Meta User']);

        $this->actingAs($user)->get(route('meta-connection.index'))
            ->assertOk()
            ->assertDontSee('Other Customer Meta User');
        $this->assertNull(MetaConnection::find($otherConnection->id));
        $this->assertSame($business->id, $user->current_business_id);
    }

    public function test_only_owner_can_disconnect_and_sensitive_tokens_are_cleared(): void
    {
        $owner = User::factory()->create();
        $business = $this->createBusinessFor($owner);
        $admin = User::factory()->create();
        $business->users()->attach($admin->id, ['role' => 'admin', 'status' => true, 'joined_at' => now()]);
        $admin->forceFill(['current_business_id' => $business->id])->save();
        $connection = $this->createMetaConnection($business, $owner);
        $page = MetaPage::create([
            'business_id' => $business->id,
            'meta_connection_id' => $connection->id,
            'meta_page_id' => 'page-1',
            'name' => 'Page',
            'page_access_token' => 'page-token',
            'is_selected' => true,
        ]);

        $this->actingAs($owner)->delete(route('meta-connection.disconnect'), ['password' => 'wrong-password'])->assertSessionHasErrors('password');
        $this->assertSame('connected', $connection->fresh()->status);
        $this->actingAs($admin)->delete(route('meta-connection.disconnect'), ['password' => 'password'])->assertForbidden();
        $this->actingAs($owner)->delete(route('meta-connection.disconnect'), ['password' => 'password'])->assertRedirect(route('meta-connection.index'));

        $this->assertNull($connection->fresh()->access_token);
        $this->assertSame('revoked', $connection->fresh()->status);
        $this->assertNull($page->fresh()->page_access_token);
        $this->assertFalse($page->fresh()->is_selected);
    }

    public function test_connection_page_never_displays_stored_tokens_or_raw_payloads(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user);
        $connection = $this->createMetaConnection($business, $user, ['access_token' => 'never-display-user-token']);
        MetaPage::create([
            'business_id' => $business->id,
            'meta_connection_id' => $connection->id,
            'meta_page_id' => 'page-visible-id',
            'name' => 'Visible Page',
            'page_access_token' => 'never-display-page-token',
            'raw_data' => ['private_debug_value' => 'never-display-raw-data'],
        ]);

        $this->actingAs($user)->get(route('meta-connection.index'))
            ->assertOk()
            ->assertSee('Visible Page')
            ->assertDontSee('never-display-user-token')
            ->assertDontSee('never-display-page-token')
            ->assertDontSee('never-display-raw-data');
    }

    public function test_asset_selection_rejects_another_tenants_assets(): void
    {
        [$user, $business, $connection] = $this->workspace();
        $page = $this->page($business->id, $connection->id, 'page-current');
        $otherUser = User::factory()->create();
        $otherBusiness = $this->createBusinessFor($otherUser);
        $otherConnection = $this->createMetaConnection($otherBusiness, $otherUser);
        $otherAd = $this->adAccount($otherBusiness->id, $otherConnection->id, 'act_other');

        $this->actingAs($user)->put(route('meta-connection.assets.update'), [
            'meta_ad_account_id' => $otherAd->id,
            'meta_page_id' => $page->id,
        ])->assertSessionHasErrors('meta_ad_account_id');
    }

    public function test_selection_keeps_only_one_asset_of_each_type_selected(): void
    {
        [$user, $business, $connection] = $this->workspace();
        $portfolioOne = MetaBusinessAccount::create(['business_id' => $business->id, 'meta_connection_id' => $connection->id, 'meta_business_id' => 'mb-1', 'name' => 'One', 'is_selected' => true]);
        $portfolioTwo = MetaBusinessAccount::create(['business_id' => $business->id, 'meta_connection_id' => $connection->id, 'meta_business_id' => 'mb-2', 'name' => 'Two']);
        $adOne = $this->adAccount($business->id, $connection->id, 'act_1', true);
        $adTwo = $this->adAccount($business->id, $connection->id, 'act_2');
        $pageOne = $this->page($business->id, $connection->id, 'page-1', true);
        $pageTwo = $this->page($business->id, $connection->id, 'page-2');
        $instagram = MetaInstagramAccount::create(['business_id' => $business->id, 'meta_connection_id' => $connection->id, 'meta_page_id' => $pageTwo->id, 'meta_instagram_account_id' => 'ig-2']);

        $this->actingAs($user)->put(route('meta-connection.assets.update'), [
            'meta_business_account_id' => $portfolioTwo->id,
            'meta_ad_account_id' => $adTwo->id,
            'meta_page_id' => $pageTwo->id,
            'meta_instagram_account_id' => $instagram->id,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($portfolioOne->fresh()->is_selected);
        $this->assertTrue($portfolioTwo->fresh()->is_selected);
        $this->assertFalse($adOne->fresh()->is_selected);
        $this->assertTrue($adTwo->fresh()->is_selected);
        $this->assertFalse($pageOne->fresh()->is_selected);
        $this->assertTrue($pageTwo->fresh()->is_selected);
        $this->assertTrue($instagram->fresh()->is_selected);
    }

    public function test_instagram_account_must_belong_to_selected_page(): void
    {
        [$user, $business, $connection] = $this->workspace();
        $ad = $this->adAccount($business->id, $connection->id, 'act_1');
        $pageOne = $this->page($business->id, $connection->id, 'page-1');
        $pageTwo = $this->page($business->id, $connection->id, 'page-2');
        $instagram = MetaInstagramAccount::create(['business_id' => $business->id, 'meta_connection_id' => $connection->id, 'meta_page_id' => $pageOne->id, 'meta_instagram_account_id' => 'ig-1']);

        $this->actingAs($user)->put(route('meta-connection.assets.update'), [
            'meta_ad_account_id' => $ad->id,
            'meta_page_id' => $pageTwo->id,
            'meta_instagram_account_id' => $instagram->id,
        ])->assertSessionHasErrors('meta_instagram_account_id');
    }

    private function workspace(): array
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user);
        $connection = $this->createMetaConnection($business, $user);

        return [$user, $business, $connection];
    }

    private function adAccount(int $businessId, int $connectionId, string $metaId, bool $selected = false): MetaAdAccount
    {
        return MetaAdAccount::create(['business_id' => $businessId, 'meta_connection_id' => $connectionId, 'meta_ad_account_id' => $metaId, 'name' => $metaId, 'is_selected' => $selected]);
    }

    private function page(int $businessId, int $connectionId, string $metaId, bool $selected = false): MetaPage
    {
        return MetaPage::create(['business_id' => $businessId, 'meta_connection_id' => $connectionId, 'meta_page_id' => $metaId, 'name' => $metaId, 'is_selected' => $selected]);
    }
}
