<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\MetaConnection;
use App\Models\MetaConnectionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'meta.app_id' => 'test-app-id',
            'meta.app_secret' => 'test-app-secret',
            'meta.redirect_uri' => 'https://app.example.com/meta-connection/callback',
            'meta.graph_version' => 'v26.0',
            'meta.oauth_base_url' => 'https://www.facebook.com',
            'meta.graph_base_url' => 'https://graph.facebook.com',
        ]);
        Http::preventStrayRequests();
    }

    public function test_oauth_redirect_contains_required_scopes_and_stores_state(): void
    {
        $user = User::factory()->create();
        $this->createBusinessFor($user);

        $response = $this->actingAs($user)->get(route('meta-connection.redirect'));

        $location = $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $state = session('meta_oauth_state');

        $response->assertRedirectContains('facebook.com/v26.0/dialog/oauth');
        $this->assertSame(config('meta.oauth_scopes'), explode(',', $query['scope']));
        $this->assertSame($state['value'], $query['state']);
        $this->assertSame($user->current_business_id, $state['business_id']);
        $this->assertNotEmpty($state['created_at']);
    }

    public function test_invalid_oauth_state_is_rejected(): void
    {
        [$user, $business] = $this->ownerWithBusiness();

        $this->actingAs($user)
            ->withSession(['meta_oauth_state' => ['value' => 'expected', 'business_id' => $business->id, 'created_at' => now()->timestamp]])
            ->get(route('meta-connection.callback', ['state' => 'wrong', 'code' => 'secret-code']))
            ->assertRedirect(route('meta-connection.index'))
            ->assertSessionHasErrors('meta');

        Http::assertNothingSent();
    }

    public function test_expired_oauth_state_is_rejected(): void
    {
        [$user, $business] = $this->ownerWithBusiness();

        $this->actingAs($user)
            ->withSession(['meta_oauth_state' => ['value' => 'state', 'business_id' => $business->id, 'created_at' => now()->subMinutes(11)->timestamp]])
            ->get(route('meta-connection.callback', ['state' => 'state', 'code' => 'secret-code']))
            ->assertSessionHasErrors('meta');

        Http::assertNothingSent();
    }

    public function test_oauth_callback_rejects_business_mismatch(): void
    {
        [$user, $business] = $this->ownerWithBusiness();
        $otherBusiness = $this->createBusinessFor($user);
        $user->forceFill(['current_business_id' => $business->id])->save();

        $this->actingAs($user)
            ->withSession(['meta_oauth_state' => ['value' => 'state', 'business_id' => $otherBusiness->id, 'created_at' => now()->timestamp]])
            ->get(route('meta-connection.callback', ['state' => 'state', 'code' => 'secret-code']))
            ->assertSessionHasErrors('meta');
    }

    public function test_missing_code_and_denied_authorization_are_handled_safely(): void
    {
        [$user, $business] = $this->ownerWithBusiness();

        $this->actingAs($user)
            ->withSession(['meta_oauth_state' => ['value' => 'one', 'business_id' => $business->id, 'created_at' => now()->timestamp]])
            ->get(route('meta-connection.callback', ['state' => 'one']))
            ->assertSessionHasErrors(['meta' => 'Meta did not return an authorization code. Please try again.']);

        $this->actingAs($user)
            ->withSession(['meta_oauth_state' => ['value' => 'two', 'business_id' => $business->id, 'created_at' => now()->timestamp]])
            ->get(route('meta-connection.callback', ['state' => 'two', 'error' => 'access_denied']))
            ->assertSessionHasErrors(['meta' => 'Meta authorization was cancelled. No connection was made.']);
    }

    public function test_successful_callback_encrypts_and_hides_tokens_and_imports_assets(): void
    {
        [$user, $business] = $this->ownerWithBusiness();
        $this->fakeSuccessfulMetaApi();

        $response = $this->actingAs($user)
            ->withSession(['meta_oauth_state' => ['value' => 'valid-state', 'business_id' => $business->id, 'created_at' => now()->timestamp]])
            ->get(route('meta-connection.callback', ['state' => 'valid-state', 'code' => 'authorization-code']));

        $response->assertRedirect(route('meta-connection.index'))->assertSessionHasNoErrors();
        $connection = MetaConnection::withoutBusinessScope()->firstOrFail();
        $rawToken = $connection->getRawOriginal('access_token');
        $page = $business->metaPages()->firstOrFail();

        $this->assertSame('long-lived-token', $connection->access_token);
        $this->assertStringNotContainsString('long-lived-token', $rawToken);
        $this->assertArrayNotHasKey('access_token', $connection->toArray());
        $this->assertArrayNotHasKey('page_access_token', $page->toArray());
        $this->assertDatabaseCount('meta_business_accounts', 1);
        $this->assertDatabaseCount('meta_ad_accounts', 1);
        $this->assertDatabaseCount('meta_pages', 1);
        $this->assertDatabaseCount('meta_instagram_accounts', 1);

        $serializedLogs = json_encode(MetaConnectionLog::withoutBusinessScope()->get()->toArray());
        $this->assertStringNotContainsString('long-lived-token', $serializedLogs);
        $this->assertStringNotContainsString('authorization-code', $serializedLogs);
        foreach (Http::recorded() as [$request]) {
            $this->assertStringNotContainsString('access_token=', $request->url());
        }
    }

    /** @return array{User, Business} */
    private function ownerWithBusiness(): array
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user);

        return [$user, $business];
    }

    private function fakeSuccessfulMetaApi(): void
    {
        $oauthExchanges = 0;
        Http::fake(function ($request) use (&$oauthExchanges) {
            $path = parse_url($request->url(), PHP_URL_PATH);

            if (str_ends_with($path, '/oauth/access_token')) {
                $oauthExchanges++;

                return Http::response([
                    'access_token' => $oauthExchanges === 1 ? 'short-lived-token' : 'long-lived-token',
                    'token_type' => 'bearer',
                    'expires_in' => 5_184_000,
                ]);
            }

            return match (true) {
                str_ends_with($path, '/me/permissions') => Http::response(['data' => [
                    ['permission' => 'ads_read', 'status' => 'granted'],
                    ['permission' => 'instagram_basic', 'status' => 'declined'],
                ]]),
                str_ends_with($path, '/me/businesses') => Http::response(['data' => [['id' => 'mb-1', 'name' => 'Meta Business', 'verification_status' => 'verified']]]),
                str_ends_with($path, '/me/adaccounts') => Http::response(['data' => [[
                    'id' => 'act_123', 'account_id' => '123', 'name' => 'Primary Ads', 'currency' => 'INR',
                    'amount_spent' => '12345', 'spend_cap' => '50000', 'balance' => '765', 'account_status' => 1,
                    'business' => ['id' => 'mb-1'],
                ]]]),
                str_ends_with($path, '/me/accounts') => Http::response(['data' => [[
                    'id' => 'page-1', 'name' => 'Main Page', 'category' => 'Retail', 'access_token' => 'page-secret-token',
                    'tasks' => ['ADVERTISE'], 'picture' => ['data' => ['url' => 'https://example.com/page.jpg']],
                    'instagram_business_account' => ['id' => 'ig-1', 'username' => 'mainshop', 'followers_count' => 25],
                ]]]),
                str_ends_with($path, '/me') => Http::response(['id' => 'meta-user-1', 'name' => 'Meta User']),
                default => Http::response(['error' => ['code' => 100, 'type' => 'OAuthException']], 400),
            };
        });
    }
}
