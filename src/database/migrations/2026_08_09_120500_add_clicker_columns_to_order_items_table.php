<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'clicker_character_count')) {
                $table->unsignedTinyInteger('clicker_character_count')->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('order_items', 'clicker_characters')) {
                $table->json('clicker_characters')->nullable()->after('clicker_character_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('order_items', 'clicker_characters')) {
                $dropColumns[] = 'clicker_characters';
            }

            if (Schema::hasColumn('order_items', 'clicker_character_count')) {
                $dropColumns[] = 'clicker_character_count';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
