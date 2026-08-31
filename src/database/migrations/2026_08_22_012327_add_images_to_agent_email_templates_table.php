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
        Schema::table('agent_email_templates', function (Blueprint $table) {
            $table->json('image_paths')->nullable()->after('body');
            $table->string('image_position', 10)->default('top')->after('image_paths');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_email_templates', function (Blueprint $table) {
            $table->dropColumn(['image_paths', 'image_position']);
        });
    }
};
