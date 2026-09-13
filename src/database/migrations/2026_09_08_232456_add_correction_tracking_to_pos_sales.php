<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sales', function (Blueprint $table): void {
            $table->unsignedBigInteger('pos_session_id')->nullable()->change();
            $table->unsignedBigInteger('recorded_by_agent_id')->nullable()->change();
            $table->unsignedInteger('correction_version')->default(0);
            $table->timestamp('voided_at')->nullable()->index();
        });
        Schema::table('pos_sale_items', function (Blueprint $table): void {
            $table->decimal('unit_cost', 12, 2)->nullable();
        });
        Schema::create('pos_sale_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('admin_user_id')->constrained('usr_admin')->restrictOnDelete();
            $table->uuid('request_token')->unique();
            $table->string('action', 20);
            $table->text('reason');
            $table->json('before')->nullable();
            $table->json('after');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sale_corrections');
        Schema::table('pos_sale_items', fn (Blueprint $table) => $table->dropColumn('unit_cost'));
        Schema::table('pos_sales', function (Blueprint $table): void {
            $table->dropColumn(['correction_version', 'voided_at']);
        });
    }
};
