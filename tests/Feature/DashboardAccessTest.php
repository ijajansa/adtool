<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_verified_active_users_can_view_dashboard(): void
    {
        $user = User::factory()->create();
        $this->createBusinessFor($user);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Active Campaigns')
            ->assertSee('Campaign performance')
            ->assertSee('Recent campaigns');
    }

    public function test_verified_users_without_a_business_are_redirected_to_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('business.onboarding.create'));
    }

    public function test_unverified_users_are_redirected_to_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_disabled_authenticated_users_are_logged_out(): void
    {
        $user = User::factory()->disabled()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'Your account has been disabled. Please contact support.',
        ]);
    }
}
