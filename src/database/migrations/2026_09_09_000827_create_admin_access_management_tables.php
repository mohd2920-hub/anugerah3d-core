<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('description', 500)->nullable();
            $table->json('permissions');
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
        });
        Schema::create('admin_role_user', function (Blueprint $table): void {
            $table->foreignId('admin_role_id')->constrained()->restrictOnDelete();
            $table->foreignId('admin_user_id')->constrained('usr_admin')->cascadeOnDelete();
            $table->primary(['admin_role_id', 'admin_user_id']);
        });
        Schema::table('usr_admin', function (Blueprint $table): void {
            $table->unsignedInteger('access_version')->default(0);
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('invitation_accepted_at')->nullable();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->string('invitation_token_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_role_user');
        Schema::dropIfExists('admin_roles');
        Schema::table('usr_admin', function (Blueprint $table): void {
            $table->dropColumn(['access_version', 'invited_at', 'invitation_accepted_at', 'invitation_expires_at', 'invitation_token_hash']);
        });
    }
};
