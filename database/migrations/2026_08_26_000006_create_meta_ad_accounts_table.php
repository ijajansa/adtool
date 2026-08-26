<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meta_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meta_business_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('meta_ad_account_id');
            $table->string('account_id')->nullable();
            $table->string('name');
            $table->string('currency', 3)->nullable();
            $table->string('timezone_name')->nullable();
            $table->decimal('timezone_offset_hours_utc', 5, 2)->nullable();
            $table->unsignedSmallInteger('account_status')->nullable();
            $table->unsignedSmallInteger('disable_reason')->nullable();
            $table->decimal('amount_spent', 15, 2)->nullable();
            $table->decimal('spend_cap', 15, 2)->nullable();
            $table->decimal('balance', 15, 2)->nullable();
            $table->boolean('is_selected')->default(false)->index();
            $table->json('raw_data')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'meta_ad_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_ad_accounts');
    }
};
