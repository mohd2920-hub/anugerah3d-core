<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_salary_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_site_operation_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->json('snapshot');
            $table->text('reason');
            $table->foreignId('updated_by')->constrained('usr_admin')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_salary_drafts');
    }
};
