<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_insights_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('meta_campaign_id')->index();
            $table->date('insight_date')->index();
            $table->char('currency_code', 3);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->decimal('frequency', 12, 4)->nullable();
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('unique_clicks')->nullable();
            $table->unsignedBigInteger('inline_link_clicks')->nullable();
            $table->unsignedBigInteger('outbound_clicks')->nullable();
            $table->unsignedBigInteger('landing_page_views')->nullable();
            $table->unsignedBigInteger('leads')->default(0);
            $table->unsignedBigInteger('messaging_conversations_started')->default(0);
            $table->unsignedBigInteger('purchases')->default(0);
            $table->decimal('spend', 15, 2)->default(0);
            $table->decimal('cpm', 15, 4)->nullable();
            $table->decimal('cpc', 15, 4)->nullable();
            $table->decimal('ctr', 12, 6)->nullable();
            $table->decimal('cost_per_result', 15, 4)->nullable();
            $table->string('result_type')->nullable();
            $table->json('conversions')->nullable();
            $table->json('actions')->nullable();
            $table->json('cost_per_action_type')->nullable();
            $table->string('attribution_setting')->nullable();
            $table->json('raw_data')->nullable();
            $table->dateTime('synced_at');
            $table->timestamps();
            $table->unique(['business_id', 'ad_campaign_id', 'insight_date'], 'campaign_insights_daily_unique');
        });

        Schema::create('campaign_insight_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_campaign_id')->constrained()->cascadeOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->char('currency_code', 3);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('results')->default(0);
            $table->string('result_type')->nullable();
            $table->decimal('spend', 15, 2)->default(0);
            $table->decimal('cpm', 15, 4)->nullable();
            $table->decimal('cpc', 15, 4)->nullable();
            $table->decimal('ctr', 12, 6)->nullable();
            $table->decimal('cost_per_result', 15, 4)->nullable();
            $table->dateTime('calculated_at');
            $table->timestamps();
            $table->unique(['business_id', 'ad_campaign_id', 'date_from', 'date_to'], 'campaign_insight_summaries_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_insight_summaries');
        Schema::dropIfExists('campaign_insights_daily');
    }
};
