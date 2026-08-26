<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_is_redirected_to_business_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('business.onboarding.create'));
    }

    public function test_business_creation_assigns_owner_and_current_business(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('business.onboarding.store'), $this->validData());

        $business = Business::sole();
        $response->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
        $this->assertSame('bright-studio', $business->slug);
        $this->assertSame($user->id, $business->created_by);
        $this->assertDatabaseHas('business_user', [
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => true,
        ]);
        $this->assertNotNull($business->users()->first()->pivot->joined_at);
        $this->assertSame($business->id, $user->fresh()->current_business_id);
    }

    public function test_onboarding_validation_errors_are_returned(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('business.onboarding.create'))
            ->post(route('business.onboarding.store'), [])
            ->assertRedirect(route('business.onboarding.create'))
            ->assertSessionHasErrors(['name', 'industry', 'country_code', 'currency_code', 'timezone']);

        $this->assertDatabaseEmpty('businesses');
    }

    public function test_business_slugs_are_unique(): void
    {
        $first = Business::factory()->create(['name' => 'Shared Name']);
        $second = Business::factory()->create(['name' => 'Shared Name']);

        $this->assertSame('shared-name', $first->slug);
        $this->assertSame('shared-name-2', $second->slug);
    }

    /** @return array<string, string> */
    private function validData(): array
    {
        return [
            'name' => 'Bright Studio',
            'industry' => 'Professional Services',
            'website' => 'https://bright.example.com',
            'phone' => '+91 98765 43210',
            'country_code' => 'IN',
            'currency_code' => 'INR',
            'timezone' => 'Asia/Kolkata',
        ];
    }
}
