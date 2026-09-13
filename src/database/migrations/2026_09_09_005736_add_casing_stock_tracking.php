<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('casing_stock_enabled')->default(false);
        });
        Schema::create('product_clicker_stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('casing_image_id')->constrained('product_clicker_images')->cascadeOnDelete();
            $table->unsignedTinyInteger('character_count');
            $table->unsignedInteger('quantity')->default(0);
            $table->unique(['casing_image_id', 'character_count']);
        });
        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('clicker_casing_image_id')->nullable()->constrained('product_clicker_images')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('clicker_casing_image_id');
        });
        Schema::dropIfExists('product_clicker_stocks');
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('casing_stock_enabled');
        });
    }
};
