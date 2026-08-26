<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meta_connection_id')->constrained()->cascadeOnDelete();
            $table->string('meta_page_id');
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('page_access_token')->nullable();
            $table->json('tasks')->nullable();
            $table->text('picture_url')->nullable();
            $table->boolean('is_selected')->default(false)->index();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'meta_page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_pages');
    }
};
