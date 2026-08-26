<?php

namespace Tests\Feature;

use App\Models\MetaAdAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaAssetSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set(['meta.graph_base_url' => 'https://graph.test', 'meta.graph_version' => 'v26.0']);
        Http::preventStrayRequests();
    }

    public function test_successful_repeated_sync_upserts_without_duplicates_and_converts_minor_units(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user);
        $connection = $this->createMetaConnection($business, $user);
        $this->fakeAssetResponses();

        $this->actingAs($user)->post(route('meta-connection.sync'))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('meta-connection.sync'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('meta_business_accounts', 1);
        $this->assertDatabaseCount('meta_ad_accounts', 1);
        $this->assertDatabaseCount('meta_pages', 1);
        $this->assertDatabaseCount('meta_instagram_accounts', 1);
        $adAccount = MetaAdAccount::firstOrFail();
        $this->assertSame('123.45', $adAccount->amount_spent);
        $this->assertSame('500.00', $adAccount->spend_cap);
        $this->assertNotNull($connection->fresh()->last_synced_at);
    }

    public function test_api_failure_is_safe_and_preserves_existing_imported_data(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user);
        $connection = $this->createMetaConnection($business, $user);
        $existing = MetaAdAccount::create([
            'business_id' => $business->id,
            'meta_connection_id' => $connection->id,
            'meta_ad_account_id' => 'act_existing',
            'name' => 'Existing account',
        ]);
        Http::fake(fn () => Http::response([
            'error' => ['message' => 'Sensitive upstream detail test-access-token', 'type' => 'OAuthException', 'code' => 190],
        ], 400));

        $response = $this->actingAs($user)->post(route('meta-connection.sync'));

        $response->assertRedirect(route('meta-connection.index'))
            ->assertSessionHasErrors(['meta' => 'The Meta connection is no longer valid. Please reconnect your account.']);
        $this->assertDatabaseHas('meta_ad_accounts', ['id' => $existing->id, 'name' => 'Existing account']);
        $this->assertNull($connection->fresh()->last_synced_at);
        $this->assertStringNotContainsString('test-access-token', json_encode(session('errors')->all()));
    }

    private function fakeAssetResponses(): void
    {
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);

            return match (true) {
                str_ends_with($path, '/me/permissions') => Http::response(['data' => [['permission' => 'ads_read', 'status' => 'granted']]]),
                str_ends_with($path, '/me/businesses') => Http::response(['data' => [['id' => 'mb-1', 'name' => 'Portfolio']]]),
                str_ends_with($path, '/me/adaccounts') => Http::response(['data' => [[
                    'id' => 'act_1', 'name' => 'Ads', 'amount_spent' => '12345', 'spend_cap' => '50000', 'balance' => '0',
                    'business' => ['id' => 'mb-1'],
                ]]]),
                str_ends_with($path, '/me/accounts') => Http::response(['data' => [[
                    'id' => 'page-1', 'name' => 'Page', 'access_token' => 'encrypted-page-source',
                    'instagram_business_account' => ['id' => 'ig-1', 'username' => 'brand'],
                ]]]),
                str_ends_with($path, '/me') => Http::response(['id' => 'meta-user', 'name' => 'Meta User']),
                default => Http::response([], 404),
            };
        });
    }
}
