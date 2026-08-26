<?php

namespace Tests\Feature;

use App\Models\MetaConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckMetaConnectionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_connection_command_marks_expired_and_reports_safe_counts(): void
    {
        $expiredUser = User::factory()->create();
        $expiredBusiness = $this->createBusinessFor($expiredUser);
        $expired = $this->createMetaConnection($expiredBusiness, $expiredUser, ['token_expires_at' => now()->subMinute()]);

        $soonUser = User::factory()->create();
        $soonBusiness = $this->createBusinessFor($soonUser);
        $this->createMetaConnection($soonBusiness, $soonUser, ['token_expires_at' => now()->addDays(2)]);

        $this->artisan('meta:check-connections')
            ->expectsOutput('Expired connections marked: 1')
            ->expectsOutput('Connected records expiring soon: 1')
            ->assertSuccessful();

        $this->assertSame(MetaConnection::STATUS_EXPIRED, $expired->fresh()->status);
    }
}
