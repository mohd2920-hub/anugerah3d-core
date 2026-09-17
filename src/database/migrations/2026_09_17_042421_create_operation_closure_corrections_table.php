<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_closure_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_site_operation_id')->constrained()->restrictOnDelete();
            $table->foreignId('replacement_operation_id')->nullable()->constrained('business_site_operations')->restrictOnDelete();
            $table->foreignId('admin_id')->constrained('usr_admin')->restrictOnDelete();
            $table->text('reason');
            $table->json('before_snapshot');
            $table->json('after_snapshot');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_closure_corrections');
    }
};
