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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('prd_code')->unique();
            $table->string('prd_name');
            $table->decimal('weight_g', 10, 1);
            $table->decimal('width_mm', 10, 1);
            $table->decimal('height_mm', 10, 1);
            $table->integer('prd_balance');
            $table->decimal('cost_rm', 10, 2);
            $table->decimal('price_selling', 10, 2);
            $table->decimal('agent_discount_default', 5, 1);
            $table->string('prd_picture')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
