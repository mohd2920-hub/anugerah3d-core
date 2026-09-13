<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('submission_token')->unique();
            $table->string('recipient_key', 50);
            $table->string('recipient_name', 150);
            $table->string('recipient_email')->nullable();
            $table->date('work_date')->index();
            $table->date('paid_date')->index();
            $table->string('site_name', 150);
            $table->string('duplicate_key', 64)->unique();
            $table->unsignedBigInteger('amount_cents');
            $table->string('reference', 150)->nullable();
            $table->text('reason');
            $table->string('proof_path')->nullable();
            $table->json('overlap_details')->nullable();
            $table->foreignId('created_by')->constrained('usr_admin')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
