<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('meta_user_id')->nullable()->index();
            $table->string('meta_user_name')->nullable();
            $table->text('access_token')->nullable();
            $table->string('token_type')->nullable();
            $table->timestamp('token_expires_at')->nullable()->index();
            $table->json('granted_scopes')->nullable();
            $table->json('declined_scopes')->nullable();
            $table->enum('status', ['pending', 'connected', 'expired', 'revoked', 'error'])->default('pending')->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_connections');
    }
};
