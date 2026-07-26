<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_closings', function (Blueprint $table) {
            $table->id();
            $table->string('week_key', 20)->unique();
            $table->timestamp('period_start')->index();
            $table->timestamp('period_end')->index();
            $table->string('status', 20)->default('completed')->index();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('email_dispatched_at')->nullable();
            $table->string('backup_path', 500)->nullable();

            $table->unsignedInteger('total_agents')->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_order_amount', 14, 2)->default(0);
            $table->unsignedInteger('total_order_units')->default(0);
            $table->unsignedInteger('total_pos_sales')->default(0);
            $table->decimal('total_pos_amount', 14, 2)->default(0);
            $table->unsignedInteger('total_new_agents')->default(0);
            $table->unsignedInteger('total_tier1_orders')->default(0);
            $table->unsignedInteger('total_tier2_orders')->default(0);
            $table->decimal('total_payable_bonus', 14, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_closings');
    }
};
