<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_sites', function (Blueprint $table) {
            $table->id();
            $table->string('site_name', 150);
            $table->string('city', 100)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_sites');
    }
};
