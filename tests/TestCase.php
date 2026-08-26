<?php

namespace Tests;

use App\Models\Business;
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
}
