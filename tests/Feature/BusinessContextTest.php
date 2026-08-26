<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_switch_to_an_accessible_business(): void
    {
        $user = User::factory()->create();
        $first = $this->createBusinessFor($user);
        $second = Business::factory()->create();
        $second->users()->attach($user->id, ['role' => 'admin', 'status' => true, 'joined_at' => now()]);

        $this->actingAs($user)
            ->post(route('businesses.switch', $second))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($second->id, $user->fresh()->current_business_id);
        $this->assertNotSame($first->id, $user->fresh()->current_business_id);
    }

    public function test_user_cannot_switch_to_another_customers_business(): void
    {
        $user = User::factory()->create();
        $this->createBusinessFor($user);
        $otherBusiness = Business::factory()->create();

        $this->actingAs($user)
            ->post(route('businesses.switch', $otherBusiness))
            ->assertForbidden();

        $this->assertNotSame($otherBusiness->id, $user->fresh()->current_business_id);
    }

    public function test_disabled_membership_cannot_access_business(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create();
        $business->users()->attach($user->id, ['role' => 'viewer', 'status' => false, 'joined_at' => now()]);
        $user->forceFill(['current_business_id' => $business->id])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('business.onboarding.create'));

        $this->assertNull($user->fresh()->current_business_id);
    }

    public function test_inactive_business_cannot_be_accessed(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user, 'owner', ['status' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('business.onboarding.create'));

        $this->actingAs($user)
            ->post(route('businesses.switch', $business))
            ->assertForbidden();
    }

    public function test_invalid_current_business_falls_back_to_first_accessible_business(): void
    {
        $user = User::factory()->create();
        $accessible = $this->createBusinessFor($user);
        $inaccessible = Business::factory()->create();
        $user->forceFill(['current_business_id' => $inaccessible->id])->save();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->assertSame($accessible->id, $user->fresh()->current_business_id);
    }

    public function test_unauthenticated_users_cannot_access_protected_routes(): void
    {
        foreach ([
            'dashboard',
            'meta-connection.index',
            'campaigns.index',
            'advertisements.create',
            'leads.index',
            'reports.index',
            'billing.index',
            'business.settings.edit',
            'profile.edit',
        ] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }
}
