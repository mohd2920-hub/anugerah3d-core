<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_site_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_site_id')->constrained()->restrictOnDelete();
            $table->timestamp('opened_at')->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['business_site_id', 'opened_at', 'closed_at'], 'business_site_operations_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_site_operations');
    }
};
