<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\TenantRecord;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenant_records', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('tenant_records');

        parent::tearDown();
    }

    public function test_tenant_scope_prevents_access_to_another_business_records(): void
    {
        $user = User::factory()->create();
        $currentBusiness = $this->createBusinessFor($user);
        $otherBusiness = Business::factory()->create();

        $currentRecord = TenantRecord::withoutBusinessScope()->create(['business_id' => $currentBusiness->id, 'name' => 'Current']);
        $otherRecord = TenantRecord::withoutBusinessScope()->create(['business_id' => $otherBusiness->id, 'name' => 'Other']);

        $this->actingAs($user);

        $this->assertSame([$currentRecord->id], TenantRecord::query()->pluck('id')->all());
        $this->assertNull(TenantRecord::query()->find($otherRecord->id));
        $this->assertSame(2, TenantRecord::withoutBusinessScope()->count());

        $autoAssigned = TenantRecord::create(['name' => 'Automatically assigned']);
        $this->assertSame($currentBusiness->id, $autoAssigned->business_id);

        Auth::logout();
        $this->assertSame(3, TenantRecord::query()->count());
    }
}
