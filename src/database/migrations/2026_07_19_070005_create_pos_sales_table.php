<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number', 40)->unique();
            $table->foreignId('pos_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('business_site_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by_agent_id')->constrained('usr_agent')->restrictOnDelete();
            $table->foreignId('sales_agent_id')->constrained('usr_agent')->restrictOnDelete();
            $table->string('customer_name', 150)->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->text('remark')->nullable();
            $table->string('payment_method', 10)->index();
            $table->string('payment_remark', 500)->nullable();
            $table->string('sale_picture_path', 250)->nullable();
            $table->string('payment_proof_path', 250)->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->timestamp('sold_at')->index();
            $table->timestamps();

            $table->index(['business_site_id', 'sold_at']);
            $table->index(['sales_agent_id', 'sold_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sales');
    }
};
