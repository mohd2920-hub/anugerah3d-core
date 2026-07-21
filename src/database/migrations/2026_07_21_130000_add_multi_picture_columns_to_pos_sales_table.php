<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->json('sale_picture_paths')->nullable()->after('sale_picture_path');
            $table->json('payment_proof_paths')->nullable()->after('payment_proof_path');
        });

        DB::table('pos_sales')
            ->select(['id', 'sale_picture_path', 'payment_proof_path'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('pos_sales')
                        ->where('id', $row->id)
                        ->update([
                            'sale_picture_paths' => $row->sale_picture_path ? json_encode([$row->sale_picture_path], JSON_THROW_ON_ERROR) : null,
                            'payment_proof_paths' => $row->payment_proof_path ? json_encode([$row->payment_proof_path], JSON_THROW_ON_ERROR) : null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropColumn(['sale_picture_paths', 'payment_proof_paths']);
        });
    }
};
