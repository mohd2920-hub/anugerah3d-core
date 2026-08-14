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
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_path', 2048);
            $table->string('alt_text')->nullable();
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->unique(['product_id', 'position']);
        });

        Schema::create('product_clicker_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('character_count');
            $table->decimal('price_rm', 10, 2);
            $table->timestamps();

            $table->unique(['product_id', 'character_count']);
        });

        Schema::create('product_clicker_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_type', 20);
            $table->string('image_path', 2048);
            $table->string('alt_text')->nullable();
            $table->unsignedTinyInteger('position');
            $table->unsignedSmallInteger('crop_width_px')->default(600);
            $table->timestamps();

            $table->unique(['product_id', 'image_type', 'position']);
            $table->index(['product_id', 'image_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_clicker_images');
        Schema::dropIfExists('product_clicker_prices');
        Schema::dropIfExists('product_images');
    }
};
