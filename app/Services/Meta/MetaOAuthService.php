<?php

namespace App\Services\Meta;

use App\Exceptions\MetaApiException;

class MetaOAuthService
{
    public function __construct(
        private readonly MetaTokenService $tokens,
        private readonly MetaGraphApiClient $client,
    ) {}

    public function authorizationUrl(string $state): string
    {
        if (! config('meta.app_id') || ! config('meta.redirect_uri')) {
            throw new MetaApiException('Meta integration is not configured. Please contact support.', ['reason' => 'configuration']);
        }

        $query = http_build_query([
            'client_id' => config('meta.app_id'),
            'redirect_uri' => config('meta.redirect_uri'),
            'state' => $state,
            'response_type' => 'code',
            'scope' => implode(',', config('meta.oauth_scopes')),
        ], '', '&', PHP_QUERY_RFC3986);

        return config('meta.oauth_base_url').'/'.config('meta.graph_version').'/dialog/oauth?'.$query;
    }

    public function exchangeCode(string $code): array
    {
        return $this->tokens->exchangeForLongLived(
            $this->tokens->exchangeAuthorizationCode($code),
        );
    }

    /** @return array{id: string, name: string|null} */
    public function fetchProfile(string $accessToken): array
    {
        $profile = $this->client->get('me', $accessToken, ['fields' => 'id,name']);

        if (empty($profile['id'])) {
            throw new MetaApiException('Meta did not return the connected user profile.', ['reason' => 'missing_profile']);
        }

        return ['id' => (string) $profile['id'], 'name' => $profile['name'] ?? null];
    }

    /** @return array{granted: list<string>, declined: list<string>} */
    public function fetchPermissions(string $accessToken): array
    {
        $permissions = $this->client->getAll('me/permissions', $accessToken);
        $granted = [];
        $declined = [];

        foreach ($permissions as $permission) {
            if (($permission['status'] ?? null) === 'granted') {
                $granted[] = (string) $permission['permission'];
            } elseif (($permission['status'] ?? null) === 'declined') {
                $declined[] = (string) $permission['permission'];
            }
        }

        sort($granted);
        sort($declined);

        return ['granted' => $granted, 'declined' => $declined];
    }

    /** @return array<string, mixed> */
    public function inspectToken(string $accessToken): array
    {
        return $this->client->postForm('debug_token', [
            'input_token' => $accessToken,
            'access_token' => config('meta.app_id').'|'.config('meta.app_secret'),
        ]);
    }
}
