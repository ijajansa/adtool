<?php

namespace App\Services\Meta;

use App\Exceptions\MetaApiException;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MetaOAuthStateService
{
    private const SESSION_KEY = 'meta_oauth_state';

    public function issue(Request $request, Business $business): string
    {
        $state = Str::random(80);

        $request->session()->put(self::SESSION_KEY, [
            'value' => $state,
            'business_id' => $business->id,
            'created_at' => now()->timestamp,
        ]);

        return $state;
    }

    public function validate(Request $request, Business $business): void
    {
        $stored = $request->session()->pull(self::SESSION_KEY);
        $provided = (string) $request->query('state', '');

        if (! is_array($stored) || ! isset($stored['value'], $stored['business_id'], $stored['created_at'])) {
            throw $this->invalid('missing_state');
        }

        if ($provided === '' || ! hash_equals((string) $stored['value'], $provided)) {
            throw $this->invalid('state_mismatch');
        }

        if (now()->timestamp - (int) $stored['created_at'] > config('meta.oauth_state_ttl')) {
            throw $this->invalid('state_expired');
        }

        if ((int) $stored['business_id'] !== (int) $business->id) {
            throw $this->invalid('business_mismatch');
        }
    }

    private function invalid(string $reason): MetaApiException
    {
        return new MetaApiException(
            'The Meta authorization session is invalid or expired. Please start again.',
            ['reason' => $reason],
        );
    }
}
