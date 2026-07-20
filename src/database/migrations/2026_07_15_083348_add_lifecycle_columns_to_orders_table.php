<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('inventory_reserved_at')->nullable()->after('admin_notification_sent_at');
            $table->timestamp('processed_at')->nullable()->after('inventory_reserved_at');
            $table->timestamp('completed_at')->nullable()->after('processed_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'inventory_reserved_at',
                'processed_at',
                'completed_at',
                'cancelled_at',
            ]);
        });
    }
};
