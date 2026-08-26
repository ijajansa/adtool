<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->foreignId('publication_attempt_id')->nullable()->after('meta_ad_id')->constrained('ad_publication_attempts')->nullOnDelete();
            $table->string('effective_status')->nullable()->after('status');
            $table->string('configured_status')->nullable()->after('effective_status');
            $table->timestamp('last_synced_at')->nullable()->after('published_at');
            $table->boolean('special_ad_category_declared')->nullable()->after('goal');
            $table->json('special_ad_categories')->nullable()->after('special_ad_category_declared');
        });

        Schema::table('ad_creatives', function (Blueprint $table) {
            $table->string('meta_image_hash')->nullable()->after('meta_creative_id');
            $table->string('meta_video_id')->nullable()->after('meta_image_hash');
            $table->string('meta_lead_form_id')->nullable()->after('meta_video_id');
            $table->text('privacy_policy_url')->nullable()->after('lead_form_name');
            $table->string('privacy_policy_link_text')->nullable()->after('privacy_policy_url');
            $table->json('requested_fields')->nullable()->after('privacy_policy_link_text');
            $table->string('completion_title')->nullable()->after('requested_fields');
            $table->text('completion_message')->nullable()->after('completion_title');
            $table->string('completion_button_text')->nullable()->after('completion_message');
            $table->text('completion_destination_url')->nullable()->after('completion_button_text');
        });
    }

    public function down(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('publication_attempt_id');
            $table->dropColumn(['effective_status', 'configured_status', 'last_synced_at', 'special_ad_category_declared', 'special_ad_categories']);
        });
        Schema::table('ad_creatives', function (Blueprint $table) {
            $table->dropColumn(['meta_image_hash', 'meta_video_id', 'meta_lead_form_id', 'privacy_policy_url', 'privacy_policy_link_text', 'requested_fields', 'completion_title', 'completion_message', 'completion_button_text', 'completion_destination_url']);
        });
    }
};
