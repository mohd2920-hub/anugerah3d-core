<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usr_agent')) {
            return;
        }

        Schema::table('usr_agent', function (Blueprint $table) {
            if (! Schema::hasColumn('usr_agent', 'bank_name')) {
                $table->string('bank_name', 120)->nullable()->after('state');
            }
            if (! Schema::hasColumn('usr_agent', 'bank_account_name')) {
                $table->string('bank_account_name', 150)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('usr_agent', 'bank_account_number')) {
                $table->string('bank_account_number', 80)->nullable()->after('bank_account_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('usr_agent')) {
            return;
        }

        Schema::table('usr_agent', function (Blueprint $table) {
            $columns = [];
            foreach (['bank_name', 'bank_account_name', 'bank_account_number'] as $column) {
                if (Schema::hasColumn('usr_agent', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
