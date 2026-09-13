<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_discount_settings', function (Blueprint $table): void {
            $table->id();
            $table->decimal('below_rm20', 5, 1)->default(10);
            $table->decimal('below_rm100', 5, 1)->default(25);
            $table->decimal('at_least_rm100', 5, 1)->default(25);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
        });
        DB::table('agent_discount_settings')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_discount_settings');
    }
};
