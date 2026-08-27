<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_budget_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_budget_type');
            $table->decimal('old_amount', 15, 2);
            $table->string('new_budget_type');
            $table->decimal('new_amount', 15, 2);
            $table->char('currency_code', 3);
            $table->string('meta_adset_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->index();
            $table->string('meta_error_code')->nullable();
            $table->text('safe_error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_budget_change_logs');
    }
};
