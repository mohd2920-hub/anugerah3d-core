<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy rows used one discount field; map it to agent discount to avoid double-deducting commission.
        DB::table('pos_sale_items')
            ->where('agent_discount_amount', 0)
            ->where('agent_discount_percentage', '>', 0)
            ->where('customer_discount_amount', '>', 0)
            ->update([
                'agent_discount_amount' => DB::raw('customer_discount_amount'),
                'customer_discount_amount' => 0,
            ]);
    }

    public function down(): void
    {
        // No-op: backfill is data correction and is intentionally not reversed.
    }
};
