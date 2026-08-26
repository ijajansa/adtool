<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('meta_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('meta_ad_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('meta_page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('meta_instagram_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('goal', ['website_traffic', 'lead_generation', 'whatsapp_messages'])->index();
            $table->enum('status', ['draft', 'ready', 'publishing', 'active', 'paused', 'completed', 'failed'])->default('draft')->index();
            $table->string('meta_campaign_id')->nullable()->index();
            $table->string('meta_adset_id')->nullable();
            $table->string('meta_ad_id')->nullable();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};
