<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('idempotency_key')->unique();
            $table->string('order_number')->nullable()->unique();
            $table->foreignId('agent_id')->constrained('usr_agent')->restrictOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->string('fulfilment_method', 20);
            $table->string('recipient_name', 150);
            $table->string('phone_number', 50);
            $table->string('delivery_address', 500)->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_method', 30);
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('delivery_fee', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->unsignedInteger('total_units');
            $table->timestamp('placed_at')->index();
            $table->timestamps();

            $table->index(['agent_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
