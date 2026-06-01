<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grisaille_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_image_url');
            $table->string('processed_image_url')->nullable();
            $table->json('value_map');
            $table->json('value_percentages');
            $table->json('palette_recommendation');
            $table->json('glazing_suggestions');
            $table->string('pdf_url')->nullable();
            $table->integer('image_width')->nullable();
            $table->integer('image_height')->nullable();
            $table->integer('total_pixels')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grisaille_analyses');
    }
};
