<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_closing_agent_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_closing_id')->constrained('weekly_closings')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('usr_agent')->restrictOnDelete();

            $table->string('agent_name', 150);
            $table->string('agent_email', 150)->nullable();
            $table->string('referrer_name', 150)->nullable();
            $table->string('referrer_email', 150)->nullable();
            $table->string('referrer_phone', 50)->nullable();

            $table->decimal('tier1_rate', 5, 2)->default(7);
            $table->decimal('tier2_rate', 5, 2)->default(3);

            $table->unsignedInteger('personal_orders_count')->default(0);
            $table->decimal('personal_order_amount', 14, 2)->default(0);
            $table->unsignedInteger('new_agents_registered')->default(0);

            $table->unsignedInteger('tier1_agents_total')->default(0);
            $table->unsignedInteger('tier2_agents_total')->default(0);
            $table->unsignedInteger('tier1_orders_count')->default(0);
            $table->unsignedInteger('tier2_orders_count')->default(0);
            $table->decimal('tier1_orders_amount', 14, 2)->default(0);
            $table->decimal('tier2_orders_amount', 14, 2)->default(0);

            $table->decimal('tier1_bonus', 14, 2)->default(0);
            $table->decimal('tier2_bonus', 14, 2)->default(0);
            $table->decimal('total_bonus', 14, 2)->default(0);

            $table->unsignedInteger('pos_sales_count')->default(0);
            $table->decimal('pos_sales_amount', 14, 2)->default(0);

            $table->string('payout_status', 20)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by_admin_id')->nullable()->constrained('usr_admin')->nullOnDelete();
            $table->string('payment_reference', 120)->nullable();
            $table->text('payment_notes')->nullable();

            $table->timestamps();

            $table->unique(['weekly_closing_id', 'agent_id'], 'weekly_closing_agent_unique');
            $table->index(['weekly_closing_id', 'payout_status'], 'weekly_closing_payout_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_closing_agent_summaries');
    }
};
