<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_instagram_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meta_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meta_page_id')->nullable()->constrained()->nullOnDelete();
            $table->string('meta_instagram_account_id');
            $table->string('username')->nullable();
            $table->string('name')->nullable();
            $table->text('profile_picture_url')->nullable();
            $table->unsignedBigInteger('followers_count')->nullable();
            $table->boolean('is_selected')->default(false)->index();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'meta_instagram_account_id'], 'meta_ig_business_account_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_instagram_accounts');
    }
};
