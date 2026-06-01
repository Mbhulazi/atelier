<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url');
            $table->string('thumbnail_url')->nullable();
            $table->integer('duration')->nullable(); // seconds
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('category')->nullable(); // tutorial, timelapse, technique, critique
            $table->string('difficulty')->nullable(); // beginner, intermediate, advanced
            $table->json('tags')->nullable();
            $table->boolean('is_free')->default(false);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('views_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_published']);
            $table->index(['category', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
