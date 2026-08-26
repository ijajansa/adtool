<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_campaign_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('location_type', ['country', 'state', 'city', 'radius']);
            $table->json('countries')->nullable();
            $table->json('states')->nullable();
            $table->json('cities')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius')->nullable();
            $table->enum('radius_unit', ['kilometer', 'mile'])->default('kilometer');
            $table->unsignedTinyInteger('age_min')->default(18);
            $table->unsignedTinyInteger('age_max')->default(65);
            $table->json('genders')->nullable();
            $table->json('interests')->nullable();
            $table->boolean('advantage_audience')->default(true);
            $table->json('raw_targeting')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_audiences');
    }
};
