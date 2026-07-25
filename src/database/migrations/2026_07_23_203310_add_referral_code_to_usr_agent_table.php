<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usr_agent', function (Blueprint $table) {
            $table->string('referral_code', 8)->nullable()->unique()->after('referrer_id');
        });

        foreach (DB::table('usr_agent')->select('id')->orderBy('id')->cursor() as $agent) {
            do {
                $code = Str::upper(Str::random(8));
            } while (DB::table('usr_agent')->where('referral_code', $code)->exists());

            DB::table('usr_agent')->where('id', $agent->id)->update(['referral_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('usr_agent', function (Blueprint $table) {
            $table->dropUnique(['referral_code']);
            $table->dropColumn('referral_code');
        });
    }
};
