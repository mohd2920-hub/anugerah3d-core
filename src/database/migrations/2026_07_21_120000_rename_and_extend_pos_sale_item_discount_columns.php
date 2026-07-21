<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->renameColumn('discount_percentage', 'agent_discount_percentage');
            $table->renameColumn('discount_amount', 'customer_discount_amount');
        });

        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->decimal('agent_discount_amount', 12, 2)
                ->default(0)
                ->after('agent_discount_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->dropColumn('agent_discount_amount');
        });

        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->renameColumn('agent_discount_percentage', 'discount_percentage');
            $table->renameColumn('customer_discount_amount', 'discount_amount');
        });
    }
};
