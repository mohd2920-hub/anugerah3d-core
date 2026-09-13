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
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->string('courier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('pickup_ready_at')->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->string('receipt_number')->nullable()->unique();
            $table->json('receipt_snapshot')->nullable();
            $table->boolean('payment_proof_requested')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropColumn(['courier', 'tracking_number', 'ready_at', 'shipped_at', 'pickup_ready_at', 'payment_confirmed_at', 'receipt_number', 'receipt_snapshot', 'payment_proof_requested']);
        });
    }
};
