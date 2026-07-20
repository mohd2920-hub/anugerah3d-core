<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('order_items')
            ->where('is_preorder', false)
            ->whereIn('order_id', function ($query): void {
                $query->select('id')
                    ->from('orders')
                    ->where('status', '!=', 'cancelled');
            })
            ->update(['reserved_quantity' => DB::raw('quantity')]);

        DB::table('orders')
            ->whereIn('id', function ($query): void {
                $query->select('order_id')
                    ->from('order_items')
                    ->where('reserved_quantity', '>', 0);
            })
            ->update(['inventory_reserved_at' => DB::raw('placed_at')]);
    }

    public function down(): void
    {
        DB::table('order_items')->update(['reserved_quantity' => 0]);
        DB::table('orders')->update(['inventory_reserved_at' => null]);
    }
};
