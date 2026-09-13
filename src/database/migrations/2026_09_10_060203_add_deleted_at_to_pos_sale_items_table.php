<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sale_items', function (Blueprint $table): void {
            $table->softDeletes();
            $table->index(['pos_sale_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        if (DB::table('pos_sale_items')->whereNotNull('deleted_at')->exists()) {
            throw new RuntimeException('Cannot remove history protection while superseded sale items exist.');
        }

        Schema::table('pos_sale_items', function (Blueprint $table): void {
            $table->dropIndex(['pos_sale_id', 'deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};
