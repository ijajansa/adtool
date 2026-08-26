<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_publication_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('idempotency_key')->unique();
            $table->enum('status', ['queued', 'validating', 'uploading_media', 'creating_form', 'creating_campaign', 'creating_adset', 'creating_creative', 'creating_ad', 'completed', 'failed', 'partial'])->index();
            $table->string('meta_campaign_id')->nullable();
            $table->string('meta_adset_id')->nullable();
            $table->string('meta_creative_id')->nullable();
            $table->string('meta_ad_id')->nullable();
            $table->string('meta_image_hash')->nullable();
            $table->string('meta_video_id')->nullable();
            $table->string('meta_lead_form_id')->nullable();
            $table->string('current_stage')->nullable();
            $table->json('request_summary')->nullable();
            $table->json('response_summary')->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_subcode')->nullable();
            $table->string('error_type')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('retryable')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_publication_attempts');
    }
};
