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
            $table->decimal('tier1_percentage', 5, 2)
                ->default(7)
                ->after('commission_percentage')
                ->comment('Tier 1 referral bonus percentage');

            $table->decimal('tier2_percentage', 5, 2)
                ->default(3)
                ->after('tier1_percentage')
                ->comment('Tier 2 referral bonus percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usr_agent', function (Blueprint $table) {
            $table->dropColumn(['tier1_percentage', 'tier2_percentage']);
        });
    }
};