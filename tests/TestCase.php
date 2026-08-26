<?php

namespace Tests;

use App\Models\Business;
use App\Models\MetaConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createBusinessFor(User $user, string $role = 'owner', array $attributes = []): Business
    {
        $creator = $role === 'owner' ? $user : User::factory()->create();
        $business = Business::factory()->create([
            ...$attributes,
            'created_by' => $attributes['created_by'] ?? $creator->id,
        ]);

        $business->users()->attach($user->id, [
            'role' => $role,
            'status' => true,
            'joined_at' => now(),
        ]);
        $user->forceFill(['current_business_id' => $business->id])->save();

        return $business;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createMetaConnection(Business $business, ?User $user = null, array $attributes = []): MetaConnection
    {
        return MetaConnection::withoutBusinessScope()->create([
            'business_id' => $business->id,
            'connected_by' => $user?->id ?? $business->created_by,
            'meta_user_id' => 'meta-user-1',
            'meta_user_name' => 'Meta User',
            'access_token' => 'test-access-token',
            'token_type' => 'bearer',
            'token_expires_at' => now()->addDays(30),
            'granted_scopes' => config('meta.oauth_scopes'),
            'declined_scopes' => [],
            'status' => MetaConnection::STATUS_CONNECTED,
            ...$attributes,
        ]);
    }
}
