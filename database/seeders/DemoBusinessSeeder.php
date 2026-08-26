<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoBusinessSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::factory()->create(['name' => 'Demo Owner', 'email' => 'owner@example.com']);
        $admin = User::factory()->create(['name' => 'Demo Admin', 'email' => 'admin@example.com']);
        $marketer = User::factory()->create(['name' => 'Demo Marketer', 'email' => 'marketer@example.com']);
        $viewer = User::factory()->create(['name' => 'Demo Viewer', 'email' => 'viewer@example.com']);

        $primary = Business::factory()->create(['name' => 'Acme Retail', 'created_by' => $owner->id]);
        $secondary = Business::factory()->create(['name' => 'Acme Hospitality', 'created_by' => $owner->id]);

        $primary->users()->attach([
            $owner->id => ['role' => 'owner', 'status' => true, 'joined_at' => now()],
            $admin->id => ['role' => 'admin', 'status' => true, 'joined_at' => now()],
            $marketer->id => ['role' => 'marketer', 'status' => true, 'joined_at' => now()],
            $viewer->id => ['role' => 'viewer', 'status' => true, 'joined_at' => now()],
        ]);
        $secondary->users()->attach($owner->id, ['role' => 'owner', 'status' => true, 'joined_at' => now()]);

        $owner->forceFill(['current_business_id' => $primary->id])->save();
        $admin->forceFill(['current_business_id' => $primary->id])->save();
        $marketer->forceFill(['current_business_id' => $primary->id])->save();
        $viewer->forceFill(['current_business_id' => $primary->id])->save();
    }
}
