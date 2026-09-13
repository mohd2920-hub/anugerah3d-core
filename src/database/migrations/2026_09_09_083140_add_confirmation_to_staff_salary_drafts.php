<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_salary_drafts', function (Blueprint $table): void {
            $table->timestamp('confirmed_at')->nullable();
        });
        Schema::table('salary_payments', function (Blueprint $table): void {
            $table->foreignId('staff_salary_draft_id')->nullable()->constrained()->restrictOnDelete();
            $table->unique(['staff_salary_draft_id', 'recipient_key'], 'salary_draft_recipient_unique');
            $table->timestamp('email_sent_at')->nullable();
            $table->text('email_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table): void {
            $table->dropUnique('salary_draft_recipient_unique');
            $table->dropConstrainedForeignId('staff_salary_draft_id');
            $table->dropColumn(['email_sent_at', 'email_error']);
        });
        Schema::table('staff_salary_drafts', function (Blueprint $table): void {
            $table->dropColumn('confirmed_at');
        });
    }
};
