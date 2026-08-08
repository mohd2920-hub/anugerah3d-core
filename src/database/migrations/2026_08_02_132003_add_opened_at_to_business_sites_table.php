<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_sites', function (Blueprint $table) {
            $table->timestamp('opened_at')->nullable()->index()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('business_sites', function (Blueprint $table) {
            $table->dropColumn('opened_at');
        });
    }
};
