<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('usr_agent')->cascadeOnDelete();
            $table->foreignId('business_site_id')->constrained()->restrictOnDelete();
            $table->timestamp('signed_in_at')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('signed_out_at')->nullable()->index();
            $table->timestamps();

            $table->index(['agent_id', 'signed_out_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
