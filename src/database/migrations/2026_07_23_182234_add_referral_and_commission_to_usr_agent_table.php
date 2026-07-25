<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('usr_agent', function (Blueprint $table) {
            $table->foreignId('referrer_id')->nullable()->after('id')->constrained('usr_agent')->nullOnDelete();
            $table->decimal('commission_percentage', 5, 2)->nullable()->after('discount_percentage')->comment('Commission assigned by admin upon approval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usr_agent', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->dropColumn(['referrer_id', 'commission_percentage']);
        });
    }
};
