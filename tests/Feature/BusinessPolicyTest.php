<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_policy_enforces_role_permissions(): void
    {
        $owner = User::factory()->create();
        $business = $this->createBusinessFor($owner);
        $admin = User::factory()->create();
        $marketer = User::factory()->create();
        $viewer = User::factory()->create();

        $business->users()->attach([
            $admin->id => ['role' => 'admin', 'status' => true, 'joined_at' => now()],
            $marketer->id => ['role' => 'marketer', 'status' => true, 'joined_at' => now()],
            $viewer->id => ['role' => 'viewer', 'status' => true, 'joined_at' => now()],
        ]);

        $this->assertTrue($owner->can('delete', $business));
        $this->assertTrue($owner->can('manageMembers', $business));
        $this->assertTrue($admin->can('update', $business));
        $this->assertTrue($admin->can('manageMembers', $business));
        $this->assertFalse($admin->can('delete', $business));
        $this->assertTrue($marketer->can('view', $business));
        $this->assertFalse($marketer->can('update', $business));
        $this->assertTrue($viewer->can('view', $business));
        $this->assertFalse($viewer->can('manageMembers', $business));
    }
}
