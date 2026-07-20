<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_business_site', function (Blueprint $table) {
            $table->foreignId('agent_id')->constrained('usr_agent')->cascadeOnDelete();
            $table->foreignId('business_site_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['agent_id', 'business_site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_business_site');
    }
};
