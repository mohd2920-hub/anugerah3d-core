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
        Schema::create('product_clicker_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('casing_image_id')->constrained('product_clicker_images')->cascadeOnDelete();
            $table->foreignId('huruf_image_id')->constrained('product_clicker_images')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('image_path', 2048);
            $table->timestamps();

            $table->unique(['product_id', 'casing_image_id', 'huruf_image_id'], 'clicker_result_pair_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_clicker_results');
    }
};
