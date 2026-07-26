<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('weekly_closing_agent_summaries')) {
            return;
        }

        Schema::table('weekly_closing_agent_summaries', function (Blueprint $table) {
            if (! Schema::hasColumn('weekly_closing_agent_summaries', 'agent_bank_name')) {
                $table->string('agent_bank_name', 120)->nullable()->after('agent_email');
            }
            if (! Schema::hasColumn('weekly_closing_agent_summaries', 'agent_bank_account_name')) {
                $table->string('agent_bank_account_name', 150)->nullable()->after('agent_bank_name');
            }
            if (! Schema::hasColumn('weekly_closing_agent_summaries', 'agent_bank_account_number')) {
                $table->string('agent_bank_account_number', 80)->nullable()->after('agent_bank_account_name');
            }

            if (! Schema::hasColumn('weekly_closing_agent_summaries', 'payment_receipt_datetime_text')) {
                $table->string('payment_receipt_datetime_text', 200)->nullable()->after('payment_reference');
            }
            if (! Schema::hasColumn('weekly_closing_agent_summaries', 'payment_attachment_path')) {
                $table->string('payment_attachment_path', 300)->nullable()->after('payment_receipt_datetime_text');
            }
            if (! Schema::hasColumn('weekly_closing_agent_summaries', 'notified_agent_at')) {
                $table->timestamp('notified_agent_at')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('weekly_closing_agent_summaries')) {
            return;
        }

        Schema::table('weekly_closing_agent_summaries', function (Blueprint $table) {
            $columns = [];
            foreach ([
                'agent_bank_name',
                'agent_bank_account_name',
                'agent_bank_account_number',
                'payment_receipt_datetime_text',
                'payment_attachment_path',
                'notified_agent_at',
            ] as $column) {
                if (Schema::hasColumn('weekly_closing_agent_summaries', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
