<?php

namespace App\Services\Meta;

use App\Exceptions\MetaApiException;
use App\Models\MetaConnection;
use Carbon\CarbonImmutable;

class MetaTokenService
{
    public function __construct(private readonly MetaGraphApiClient $client) {}

    /** @return array{access_token: string, token_type: string|null, expires_in: int|null} */
    public function exchangeAuthorizationCode(string $code): array
    {
        $this->ensureConfigured();
        $response = $this->client->postForm('oauth/access_token', [
            'client_id' => config('meta.app_id'),
            'client_secret' => config('meta.app_secret'),
            'redirect_uri' => config('meta.redirect_uri'),
            'code' => $code,
        ]);

        return $this->normalize($response);
    }

    /** @param array{access_token: string, token_type: string|null, expires_in: int|null} $shortLived */
    public function exchangeForLongLived(array $shortLived): array
    {
        try {
            $response = $this->client->postForm('oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => config('meta.app_id'),
                'client_secret' => config('meta.app_secret'),
                'fb_exchange_token' => $shortLived['access_token'],
            ]);

            return $this->normalize($response);
        } catch (MetaApiException) {
            return $shortLived;
        }
    }

    public function expiresAt(?int $expiresIn): ?CarbonImmutable
    {
        return $expiresIn ? CarbonImmutable::now()->addSeconds($expiresIn) : null;
    }

    public function isExpired(MetaConnection $connection): bool
    {
        return $connection->token_expires_at?->isPast() ?? false;
    }

    public function isApproachingExpiry(MetaConnection $connection): bool
    {
        if (! $connection->token_expires_at || $this->isExpired($connection)) {
            return false;
        }

        return $connection->token_expires_at->lte(now()->addDays(config('meta.expiry_warning_days')));
    }

    /** @param array<string, mixed> $response */
    private function normalize(array $response): array
    {
        if (empty($response['access_token'])) {
            throw new MetaApiException('Meta did not return a usable access token.', ['reason' => 'missing_access_token']);
        }

        return [
            'access_token' => (string) $response['access_token'],
            'token_type' => isset($response['token_type']) ? (string) $response['token_type'] : null,
            'expires_in' => isset($response['expires_in']) ? (int) $response['expires_in'] : null,
        ];
    }

    private function ensureConfigured(): void
    {
        if (! config('meta.app_id') || ! config('meta.app_secret') || ! config('meta.redirect_uri')) {
            throw new MetaApiException('Meta integration is not configured. Please contact support.', ['reason' => 'configuration']);
        }
    }
}
