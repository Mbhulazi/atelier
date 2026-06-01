<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('painter_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->text('specialties')->nullable();
            $table->text('style')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->text('featured_image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('featured_order')->default(0);
            $table->json('social_links')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('painter_profiles');
    }
};
