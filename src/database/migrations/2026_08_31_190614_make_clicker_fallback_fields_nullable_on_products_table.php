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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('weight_g', 10, 1)->nullable()->change();
            $table->decimal('width_mm', 10, 1)->nullable()->change();
            $table->decimal('height_mm', 10, 1)->nullable()->change();
            $table->integer('prd_balance')->nullable()->change();
            $table->decimal('cost_rm', 10, 2)->nullable()->change();
            $table->decimal('price_selling', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('weight_g', 10, 1)->nullable(false)->change();
            $table->decimal('width_mm', 10, 1)->nullable(false)->change();
            $table->decimal('height_mm', 10, 1)->nullable(false)->change();
            $table->integer('prd_balance')->nullable(false)->change();
            $table->decimal('cost_rm', 10, 2)->nullable(false)->change();
            $table->decimal('price_selling', 10, 2)->nullable(false)->change();
        });
    }
};
