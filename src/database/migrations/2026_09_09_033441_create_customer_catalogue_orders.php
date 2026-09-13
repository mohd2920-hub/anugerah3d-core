<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('idempotency_key')->unique();
            $table->uuid('tracking_token')->unique();
            $table->string('order_number')->nullable()->unique();
            $table->foreignId('agent_id')->constrained('usr_agent')->restrictOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->string('fulfilment_method', 20);
            $table->string('recipient_name', 150);
            $table->string('phone_number', 50);
            $table->string('delivery_address', 500)->nullable();
            $table->text('notes')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->string('payment_method', 30);
            $table->string('payment_status', 30)->default('unpaid');
            $table->json('payment_proof_paths')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('delivery_fee', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->unsignedInteger('total_units');
            $table->decimal('commission_rate', 5, 2)->default(25);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('refunded_product_amount', 12, 2)->default(0);
            foreach (['placed_at', 'processed_at', 'completed_at', 'cancelled_at', 'inventory_reserved_at', 'admin_notification_sent_at'] as $column) {
                $table->timestamp($column)->nullable();
            }
            $table->timestamps();
            $table->index(['agent_id', 'status']);
        });
        Schema::create('customer_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('customer_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_code');
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('reserved_quantity')->default(0);
            foreach (['unit_selling_price', 'unit_price', 'line_total'] as $column) {
                $table->decimal($column, 12, 2);
            }
            $table->decimal('discount_percentage', 5, 1)->default(0);
            $table->boolean('is_preorder')->default(false);
            $table->unsignedTinyInteger('clicker_character_count')->nullable();
            $table->json('clicker_characters')->nullable();
            $table->string('clicker_casing_image_path')->nullable();
            $table->string('clicker_huruf_image_path')->nullable();
            $table->foreignId('clicker_casing_image_id')->nullable()->constrained('product_clicker_images')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('customer_commission_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('customer_orders')->restrictOnDelete();
            $table->unsignedBigInteger('admin_id');
            $table->string('type', 20);
            $table->decimal('amount', 12, 2);
            $table->decimal('previous_amount', 12, 2)->nullable();
            $table->decimal('cash_amount', 12, 2)->nullable();
            $table->string('reference')->nullable();
            $table->string('proof_path')->nullable();
            $table->text('reason');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_commission_entries');
        Schema::dropIfExists('customer_order_items');
        Schema::dropIfExists('customer_orders');
    }
};
