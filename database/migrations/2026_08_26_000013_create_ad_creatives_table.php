<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_creatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_campaign_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('format', ['single_image', 'single_video']);
            $table->text('primary_text');
            $table->string('headline')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('call_to_action');
            $table->text('destination_url')->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('lead_form_name')->nullable();
            $table->string('media_path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('meta_creative_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_creatives');
    }
};
