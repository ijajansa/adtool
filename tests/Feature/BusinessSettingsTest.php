<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_business_settings_without_changing_slug(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user, 'owner', ['name' => 'Original Name']);
        $slug = $business->slug;

        $this->actingAs($user)
            ->put(route('business.settings.update'), $this->validData('Renamed Business'))
            ->assertRedirect(route('business.settings.edit'))
            ->assertSessionHasNoErrors();

        $business->refresh();
        $this->assertSame('Renamed Business', $business->name);
        $this->assertSame($slug, $business->slug);
    }

    public function test_admin_can_update_business_settings(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user, 'admin');

        $this->actingAs($user)
            ->put(route('business.settings.update'), $this->validData('Admin Updated'))
            ->assertRedirect(route('business.settings.edit'));

        $this->assertSame('Admin Updated', $business->fresh()->name);
    }

    public function test_marketer_cannot_update_business_settings(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user, 'marketer');

        $this->actingAs($user)
            ->put(route('business.settings.update'), $this->validData('Forbidden Update'))
            ->assertForbidden();

        $this->assertNotSame('Forbidden Update', $business->fresh()->name);
    }

    public function test_viewer_cannot_update_business_settings(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusinessFor($user, 'viewer');

        $this->actingAs($user)
            ->put(route('business.settings.update'), $this->validData('Forbidden Update'))
            ->assertForbidden();

        $this->assertNotSame('Forbidden Update', $business->fresh()->name);
    }

    public function test_owner_can_replace_a_valid_business_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('business-logos/old.png', 'old-logo');

        $user = User::factory()->create();
        $business = $this->createBusinessFor($user, 'owner', ['logo_path' => 'business-logos/old.png']);
        $logo = UploadedFile::fake()->image('new-logo.png', 300, 300);

        $this->actingAs($user)
            ->put(route('business.settings.update'), [
                ...$this->validData('Logo Business'),
                'logo' => $logo,
            ])
            ->assertRedirect(route('business.settings.edit'))
            ->assertSessionHasNoErrors();

        $business->refresh();
        Storage::disk('public')->assertExists($business->logo_path);
        Storage::disk('public')->assertMissing('business-logos/old.png');
    }

    /** @return array<string, string> */
    private function validData(string $name): array
    {
        return [
            'name' => $name,
            'industry' => 'Retail',
            'website' => 'https://example.com',
            'phone' => '+91 98765 43210',
            'country_code' => 'IN',
            'currency_code' => 'INR',
            'timezone' => 'Asia/Kolkata',
        ];
    }
}
