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
        Schema::create('usr_agent', function (Blueprint $table) {
            $table->id();
            $table->string('login_id', 100)->unique();
            $table->string('agt_name', 150);
            $table->string('id_number', 50)->nullable()->unique();
            $table->string('email', 100)->unique();
            $table->string('phone_number', 50)->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('agt_status', 15)->default('active')->index()->comment('active, inactive, blocked, suspended');
            $table->string('address', 250)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable()->index();
            $table->decimal('discount_percentage', 5, 1)->default(0)->comment('percentage');
            $table->string('profile_picture', 250)->nullable()->comment('s3 link');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->decimal('total_sale', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usr_agent');
    }
};
