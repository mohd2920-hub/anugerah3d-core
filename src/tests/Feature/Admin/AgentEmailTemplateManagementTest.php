<?php

namespace Tests\Feature\Admin;

use App\Mail\AgentTemplateMail;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\AgentEmailTemplate;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AgentEmailTemplateManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_agent_email_templates_index_and_agents_shortcut(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, "admin")
            ->get($this->adminUrl("/agents"))
            ->assertOk()
            ->assertSeeText("Email to Agen");

        $this->actingAs($admin, "admin")
            ->get($this->adminUrl("/agent-email-templates"))
            ->assertOk()
            ->assertViewIs("components.admin.agent-email-templates.index-page")
            ->assertSeeText("Agent email templates")
            ->assertSeeText("Create Template");
    }

    public function test_admin_can_render_agent_email_template_create_page_with_search_picker(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, "admin")
            ->get($this->adminUrl("/agent-email-templates/create"))
            ->assertOk()
            ->assertSeeText("Top 10 results")
            ->assertSeeText("Selected list");
    }

    public function test_admin_can_create_selected_agent_email_template_without_sending_email(): void
    {
        $admin = AdminUser::factory()->create();
        $firstAgent = Agent::factory()->create();
        $secondAgent = Agent::factory()->create();

        Mail::fake();

        $this->actingAs($admin, "admin")
            ->post($this->adminUrl("/agent-email-templates"), [
                "name" => "Ogos 2026 Update",
                "recipient_scope" => AgentEmailTemplate::RecipientSelectedAgents,
                "agent_ids" => [$firstAgent->id, $secondAgent->id],
                "subject" => "Pengumuman Ogos",
                "body" => "Assalamualaikum,\nAda pengumuman baru untuk semua ejen terpilih.",
            ])
            ->assertRedirect();

        $template = AgentEmailTemplate::query()->where("name", "Ogos 2026 Update")->firstOrFail();

        $this->assertSame(AgentEmailTemplate::RecipientSelectedAgents, $template->recipient_scope);
        $this->assertSame([$firstAgent->id, $secondAgent->id], $template->selectedAgentIds());
        $this->assertSame($admin->id, $template->created_by_admin_id);
        Mail::assertNothingSent();
    }

    public function test_selected_agent_template_requires_at_least_one_recipient(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, "admin")
            ->from($this->adminUrl("/agent-email-templates/create"))
            ->post($this->adminUrl("/agent-email-templates"), [
                "name" => "Empty selection",
                "recipient_scope" => AgentEmailTemplate::RecipientSelectedAgents,
                "subject" => "Subjek",
                "body" => "Kandungan",
            ])
            ->assertRedirect($this->adminUrl("/agent-email-templates/create"))
            ->assertSessionHasErrors("agent_ids");
    }

    public function test_admin_can_send_template_only_to_selected_agents_with_valid_email_addresses(): void
    {
        $admin = AdminUser::factory()->create();
        $selectedAgent = Agent::factory()->create(["email" => "selected@example.com"]);
        $invalidSelectedAgent = Agent::factory()->create(["email" => "invalid-email"]);
        $unselectedAgent = Agent::factory()->create(["email" => "other@example.com"]);

        $template = AgentEmailTemplate::query()->create([
            "name" => "Selected Promo",
            "recipient_scope" => AgentEmailTemplate::RecipientSelectedAgents,
            "selected_agent_ids" => [$selectedAgent->id, $invalidSelectedAgent->id],
            "subject" => "Promo khas",
            "body" => "Ini promosi terbaru daripada Anugerah3D.",
            "created_by_admin_id" => $admin->id,
        ]);

        Mail::fake();

        $this->actingAs($admin, "admin")
            ->post($this->adminUrl("/agent-email-templates/{$template->id}/send"))
            ->assertRedirect()
            ->assertSessionHas("success");

        $template->refresh();

        $this->assertNotNull($template->last_sent_at);
        $this->assertSame($admin->id, $template->last_sent_by_admin_id);

        Mail::assertSent(AgentTemplateMail::class, 1);
        Mail::assertSent(
            AgentTemplateMail::class,
            fn (AgentTemplateMail $mail): bool => $mail->recipient->is($selectedAgent)
                && $mail->hasTo("selected@example.com"),
        );

        Mail::assertNotSent(
            AgentTemplateMail::class,
            fn (AgentTemplateMail $mail): bool => $mail->recipient->is($invalidSelectedAgent)
                || $mail->recipient->is($unselectedAgent),
        );
    }

    public function test_agent_template_mail_uses_anugerah3d_branding_and_body_content(): void
    {
        $template = AgentEmailTemplate::query()->make([
            "name" => "Branding Test",
            "recipient_scope" => AgentEmailTemplate::RecipientAllAgents,
            "subject" => "Notis rasmi",
            "body" => "Baris pertama.\nBaris kedua.",
        ]);
        $agent = Agent::factory()->create(["agt_name" => "Aisyah Agent"]);

        $mail = new AgentTemplateMail($template, $agent);

        $mail->assertSeeInHtml("Anugerah3D");
        $mail->assertSeeInHtml("Notis rasmi");
        $mail->assertSeeInHtml("Hi Aisyah Agent");
        $mail->assertSeeInHtml("Baris pertama.");
        $mail->assertSeeInHtml("Baris kedua.");
    }

    private function adminUrl(string $path): string
    {
        return "http://".config("domains.admin").$path;
    }
}
