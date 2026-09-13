<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sale_items', function (Blueprint $table): void {
            $table->index(['pos_sale_id', 'product_id'], 'pos_sale_items_sale_product_index');
            $table->dropUnique(['pos_sale_id', 'product_id']);
            $table->json('clicker_configuration')->nullable();
            $table->foreignId('stock_casing_image_id')->nullable()->constrained('product_clicker_images')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('pos_sale_items')->select('pos_sale_id', 'product_id')->groupBy('pos_sale_id', 'product_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new LogicException('Cannot restore the old unique constraint while sales contain multiple product selections.');
        }

        Schema::table('pos_sale_items', function (Blueprint $table): void {
            $table->unique(['pos_sale_id', 'product_id']);
            $table->dropIndex('pos_sale_items_sale_product_index');
            $table->dropConstrainedForeignId('stock_casing_image_id');
            $table->dropColumn('clicker_configuration');
        });
    }
};
