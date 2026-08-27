<?php

namespace App\Services\Meta;

use App\Exceptions\MetaApiException;
use App\Models\MetaAdAccount;
use App\Models\MetaBusinessAccount;
use App\Models\MetaConnection;
use App\Models\MetaInstagramAccount;
use App\Models\MetaPage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MetaAssetSyncService
{
    public function __construct(
        private readonly MetaGraphApiClient $client,
        private readonly MetaOAuthService $oauth,
        private readonly MetaTokenService $tokens,
    ) {}

    /** @return array{businesses: int, ad_accounts: int, pages: int, instagram_accounts: int} */
    public function synchronize(MetaConnection $connection): array
    {
        if (! $connection->access_token) {
            throw new MetaApiException('The Meta connection has no usable access token.', ['reason' => 'missing_access_token']);
        }

        if ($this->tokens->isExpired($connection)) {
            $connection->update(['status' => MetaConnection::STATUS_EXPIRED]);
            throw new MetaApiException('The Meta connection has expired. Please reconnect it.', ['reason' => 'expired_token']);
        }

        $accessToken = $connection->access_token;

        // Fetch every required endpoint before writing. A failed API call therefore leaves
        // all previously imported metadata untouched.
        $profile = $this->oauth->fetchProfile($accessToken);
        $permissions = $this->oauth->fetchPermissions($accessToken);
        $businesses = $this->client->getAll('me/businesses', $accessToken, [
            'fields' => 'id,name,verification_status',
            'limit' => 100,
        ]);
        $adAccounts = $this->client->getAll('me/adaccounts', $accessToken, [
            'fields' => 'id,account_id,name,currency,timezone_name,timezone_offset_hours_utc,account_status,disable_reason,amount_spent,spend_cap,balance,business',
            'limit' => 100,
        ]);
        $pages = $this->client->getAll('me/accounts', $accessToken, [
            'fields' => 'id,name,category,access_token,tasks,picture{url},instagram_business_account{id,username,name,profile_picture_url,followers_count},has_whatsapp_number,has_whatsapp_business_number,whatsapp_number,leadgen_tos_accepted,has_lead_access',
            'limit' => 100,
        ]);

        $instagramCount = 0;
        $syncedAt = now();

        DB::transaction(function () use (
            $connection,
            $profile,
            $permissions,
            $businesses,
            $adAccounts,
            $pages,
            $syncedAt,
            &$instagramCount,
        ): void {
            $businessAccountIds = [];

            foreach ($businesses as $item) {
                $account = MetaBusinessAccount::updateOrCreate(
                    ['business_id' => $connection->business_id, 'meta_business_id' => (string) $item['id']],
                    [
                        'meta_connection_id' => $connection->id,
                        'name' => (string) ($item['name'] ?? 'Unnamed Meta business'),
                        'verification_status' => $item['verification_status'] ?? null,
                        'raw_data' => $item,
                    ],
                );
                $businessAccountIds[(string) $item['id']] = $account->id;
            }

            foreach ($adAccounts as $item) {
                $metaBusinessId = data_get($item, 'business.id');
                MetaAdAccount::updateOrCreate(
                    ['business_id' => $connection->business_id, 'meta_ad_account_id' => (string) $item['id']],
                    [
                        'meta_connection_id' => $connection->id,
                        'meta_business_account_id' => $metaBusinessId ? ($businessAccountIds[(string) $metaBusinessId] ?? null) : null,
                        'account_id' => $item['account_id'] ?? null,
                        'name' => (string) ($item['name'] ?? 'Unnamed ad account'),
                        'currency' => $item['currency'] ?? null,
                        'timezone_name' => $item['timezone_name'] ?? null,
                        'timezone_offset_hours_utc' => $item['timezone_offset_hours_utc'] ?? null,
                        'account_status' => $item['account_status'] ?? null,
                        'disable_reason' => $item['disable_reason'] ?? null,
                        'amount_spent' => $this->minorUnitsToMajor($item['amount_spent'] ?? null),
                        'spend_cap' => $this->minorUnitsToMajor($item['spend_cap'] ?? null),
                        'balance' => $this->minorUnitsToMajor($item['balance'] ?? null),
                        'raw_data' => $item,
                        'last_synced_at' => $syncedAt,
                    ],
                );
            }

            foreach ($pages as $item) {
                $pageAttributes = [
                    'meta_connection_id' => $connection->id,
                    'name' => (string) ($item['name'] ?? 'Unnamed Facebook Page'),
                    'category' => $item['category'] ?? null,
                    'tasks' => $item['tasks'] ?? null,
                    'picture_url' => data_get($item, 'picture.data.url') ?? data_get($item, 'picture.url'),
                    'raw_data' => Arr::except($item, ['access_token']),
                ];

                if (! empty($item['access_token'])) {
                    $pageAttributes['page_access_token'] = (string) $item['access_token'];
                }

                $page = MetaPage::updateOrCreate(
                    ['business_id' => $connection->business_id, 'meta_page_id' => (string) $item['id']],
                    $pageAttributes,
                );

                $instagram = $item['instagram_business_account'] ?? null;
                if (is_array($instagram) && ! empty($instagram['id'])) {
                    MetaInstagramAccount::updateOrCreate(
                        ['business_id' => $connection->business_id, 'meta_instagram_account_id' => (string) $instagram['id']],
                        [
                            'meta_connection_id' => $connection->id,
                            'meta_page_id' => $page->id,
                            'username' => $instagram['username'] ?? null,
                            'name' => $instagram['name'] ?? null,
                            'profile_picture_url' => $instagram['profile_picture_url'] ?? null,
                            'followers_count' => $instagram['followers_count'] ?? null,
                            'raw_data' => $instagram,
                        ],
                    );
                    $instagramCount++;
                }
            }

            $connection->update([
                'meta_user_id' => $profile['id'],
                'meta_user_name' => $profile['name'],
                'granted_scopes' => $permissions['granted'],
                'declined_scopes' => $permissions['declined'],
                'status' => MetaConnection::STATUS_CONNECTED,
                'last_synced_at' => $syncedAt,
                'last_error' => null,
            ]);
        });

        return [
            'businesses' => count($businesses),
            'ad_accounts' => count($adAccounts),
            'pages' => count($pages),
            'instagram_accounts' => $instagramCount,
        ];
    }

    /**
     * Meta ad-account monetary fields are returned as integer minor units. The schema stores
     * normal currency units using the configured currency precision.
     */
    public function minorUnitsToMajor(int|float|string|null $value, string $currency = 'USD'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = (string) $value;
        if (! preg_match('/^-?\d+$/', $raw)) {
            return null;
        }

        $negative = str_starts_with($raw, '-');
        $digits = ltrim($raw, '-0') ?: '0';
        $precision = config('meta_publishing.currency_precision.'.strtoupper($currency));
        if ($precision === null) {
            return null;
        }
        if ($precision === 0) {
            return ($negative ? '-' : '').$digits;
        }
        $digits = str_pad($digits, $precision + 1, '0', STR_PAD_LEFT);
        $major = ltrim(substr($digits, 0, -$precision), '0') ?: '0';

        return ($negative ? '-' : '').$major.'.'.substr($digits, -$precision);
    }
}
