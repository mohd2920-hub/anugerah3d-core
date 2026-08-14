<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("agent_email_templates", function (Blueprint $table) {
            $table->id();
            $table->string("name", 150);
            $table->string("recipient_scope", 30)->default("all_agents");
            $table->json("selected_agent_ids")->nullable();
            $table->string("subject", 200);
            $table->longText("body");
            $table->foreignId("created_by_admin_id")->nullable()->constrained("usr_admin")->nullOnDelete();
            $table->timestamp("last_sent_at")->nullable();
            $table->foreignId("last_sent_by_admin_id")->nullable()->constrained("usr_admin")->nullOnDelete();
            $table->timestamps();

            $table->index("recipient_scope");
            $table->index("last_sent_at");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("agent_email_templates");
    }
};
