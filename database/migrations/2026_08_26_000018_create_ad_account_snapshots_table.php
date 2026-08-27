<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_account_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meta_ad_account_id')->constrained('meta_ad_accounts')->cascadeOnDelete();
            $table->char('currency_code', 3);
            $table->string('account_status')->nullable();
            $table->decimal('amount_spent', 15, 2)->nullable();
            $table->decimal('balance', 15, 2)->nullable();
            $table->decimal('spend_cap', 15, 2)->nullable();
            $table->string('funding_source_status')->nullable();
            $table->string('disable_reason')->nullable();
            $table->dateTime('snapshot_at');
            $table->json('raw_data')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'snapshot_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_account_snapshots');
    }
};
