<?php

namespace App\Services\Meta;

use App\Exceptions\MetaApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class MetaGraphApiClient
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, string $accessToken, array $query = []): array
    {
        return $this->send(
            fn (PendingRequest $request) => $request->withToken($accessToken)->get($this->url($path), $query),
            $path,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $path, string $accessToken, array $payload = []): array
    {
        return $this->send(
            fn (PendingRequest $request) => $request->withToken($accessToken)->post($this->url($path), $payload),
            $path,
        );
    }

    /** @param array<string, mixed> $payload */
    public function postFormWithToken(string $path, string $accessToken, array $payload = []): array
    {
        $encoded = collect($payload)->map(fn ($value) => is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value)->all();

        return $this->send(
            fn (PendingRequest $request) => $request->withToken($accessToken)->asForm()->post($this->url($path), $encoded),
            $path,
        );
    }

    /** @param array<string, string|int> $fields */
    public function postMultipart(string $path, string $accessToken, string $field, string $contents, string $filename, string $mimeType, array $fields = []): array
    {
        return $this->send(
            fn (PendingRequest $request) => $request->withToken($accessToken)
                ->attach($field, $contents, $filename, ['Content-Type' => $mimeType])
                ->post($this->url($path), $fields),
            $path,
        );
    }

    /**
     * Used for OAuth exchanges so credentials and tokens stay in the request body, not the URL.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function postForm(string $path, array $payload): array
    {
        return $this->send(
            fn (PendingRequest $request) => $request->asForm()->post($this->url($path), $payload),
            $path,
        );
    }

    /**
     * Follow cursor pagination without using Meta's `paging.next` URL, which may contain a token.
     *
     * @param  array<string, mixed>  $query
     * @return list<array<string, mixed>>
     */
    public function getAll(string $path, string $accessToken, array $query = []): array
    {
        $items = [];
        $after = null;

        do {
            $pageQuery = $after ? [...$query, 'after' => $after] : $query;
            $response = $this->get($path, $accessToken, $pageQuery);
            $items = [...$items, ...array_values($response['data'] ?? [])];
            $after = data_get($response, 'paging.cursors.after');
        } while ($after);

        return $items;
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout(config('meta.http_timeout'))
            ->connectTimeout(config('meta.connect_timeout'));
    }

    /**
     * @param  callable(PendingRequest): Response  $callback
     * @return array<string, mixed>
     */
    private function send(callable $callback, string $path): array
    {
        try {
            $response = $callback($this->request());
        } catch (ConnectionException $exception) {
            throw new MetaApiException(
                'Meta could not be reached. Please try again shortly.',
                ['reason' => 'connection_failure', 'path' => $path],
                $exception,
            );
        } catch (Throwable $exception) {
            throw new MetaApiException(
                'The Meta request could not be completed.',
                ['reason' => 'request_failure', 'path' => $path],
                $exception,
            );
        }

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $error = $response->json('error', []);
        $code = (int) ($error['code'] ?? 0);
        $safeMessage = match (true) {
            $response->status() === 429, in_array($code, [4, 17, 32, 613], true) => 'Meta is rate limiting requests. Please try again later.',
            $code === 190 => 'The Meta connection is no longer valid. Please reconnect your account.',
            default => 'Meta rejected the request. Please try again or reconnect your account.',
        };

        throw new MetaApiException($safeMessage, [
            'reason' => 'api_error',
            'path' => $path,
            'http_status' => $response->status(),
            'meta_code' => $code ?: null,
            'meta_subcode' => $error['error_subcode'] ?? null,
            'meta_type' => $error['type'] ?? null,
        ]);
    }

    private function url(string $path): string
    {
        return config('meta.graph_base_url').'/'.config('meta.graph_version').'/'.ltrim($path, '/');
    }
}
