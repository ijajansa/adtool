<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_spending_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('maximum_daily_budget', 15, 2)->nullable();
            $table->decimal('maximum_lifetime_budget', 15, 2)->nullable();
            $table->decimal('monthly_warning_amount', 15, 2)->nullable();
            $table->decimal('monthly_hard_limit', 15, 2)->nullable();
            $table->decimal('require_owner_approval_above', 15, 2)->nullable();
            $table->char('currency_code', 3);
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_spending_controls');
    }
};
