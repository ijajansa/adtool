<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_business_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meta_connection_id')->constrained()->cascadeOnDelete();
            $table->string('meta_business_id');
            $table->string('name');
            $table->string('verification_status')->nullable();
            $table->boolean('is_selected')->default(false)->index();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'meta_business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_business_accounts');
    }
};
