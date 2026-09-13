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
        Schema::table('product_clicker_prices', function (Blueprint $table) {
            $table->decimal('cost_rm', 10, 2)->nullable()->after('price_rm');
            $table->decimal('weight_g', 10, 2)->nullable()->after('cost_rm');
            $table->decimal('width_mm', 10, 2)->nullable()->after('weight_g');
            $table->decimal('height_mm', 10, 2)->nullable()->after('width_mm');
            $table->decimal('length_mm', 10, 2)->nullable()->after('height_mm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_clicker_prices', function (Blueprint $table) {
            $table->dropColumn(['cost_rm', 'weight_g', 'width_mm', 'height_mm', 'length_mm']);
        });
    }
};
