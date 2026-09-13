<?php

namespace Tests\Feature\Admin;

use App\Mail\AgentTemplateMail;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\AgentEmailTemplate;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentEmailTemplateManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_template_video_upload_replace_remove_and_mail_link(): void
    {
        $this->withoutVite();
        $this->app->usePublicPath(sys_get_temp_dir().'/email-video-test-'.Str::uuid());
        Mail::fake();
        $admin = AdminUser::factory()->superAdmin()->create();
        $data = ['name' => 'Video test', 'recipient_scope' => 'all_agents', 'subject' => 'Video', 'body' => 'Body', 'image_position' => 'top'];
        $this->actingAs($admin, 'admin')->post($this->adminUrl('/agent-email-templates'), $data + ['template_video' => UploadedFile::fake()->create('video.mp4', 100, 'video/mp4')])->assertSessionHasNoErrors();
        $template = AgentEmailTemplate::where('name', 'Video test')->firstOrFail();
        $firstPath = $template->video_path;
        $this->assertFileExists(public_path($firstPath));
        $html = (new AgentTemplateMail($template, Agent::factory()->create()))->render();
        $this->assertStringContainsString('Tonton Video', $html);
        $this->assertStringContainsString(asset($firstPath), $html);
        $url = $this->adminUrl('/agent-email-templates/'.$template->id);
        $this->put($url, $data + ['template_video' => UploadedFile::fake()->create('invalid.txt', 1, 'text/plain')])->assertSessionHasErrors('template_video');
        $this->assertSame($firstPath, $template->fresh()->video_path);
        $this->put($url, $data + ['template_video' => UploadedFile::fake()->create('large.mp4', 20481, 'video/mp4')])->assertSessionHasErrors('template_video');
        $this->put($url, $data + ['template_video' => UploadedFile::fake()->create('new.webm', 100, 'video/webm')])->assertSessionHasNoErrors();
        $secondPath = $template->fresh()->video_path;
        $this->assertNotSame($firstPath, $secondPath);
        $this->assertFileExists(public_path($firstPath));
        $this->assertFileExists(public_path($secondPath));
        $this->put($url, $data + ['remove_template_video' => 1])->assertSessionHasNoErrors();
        $this->assertNull($template->fresh()->video_path);
        $this->assertFileExists(public_path($secondPath));
        Mail::assertNothingSent();
    }

    public function test_admin_can_view_agent_email_templates_index_and_agents_shortcut(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl('/agents'))
            ->assertOk()
            ->assertSeeText('Email to Agen');

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl('/agent-email-templates'))
            ->assertOk()
            ->assertViewIs('components.admin.agent-email-templates.index-page')
            ->assertSeeText('Agent email templates')
            ->assertSeeText('Create Template');
    }

    public function test_admin_can_render_agent_email_template_create_page_with_search_picker(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl('/agent-email-templates/create'))
            ->assertOk()
            ->assertSeeText('Top 10 results')
            ->assertSeeText('Selected list');
    }

    public function test_admin_can_create_selected_agent_email_template_without_sending_email(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $firstAgent = Agent::factory()->create();
        $secondAgent = Agent::factory()->create();

        Mail::fake();

        $this->actingAs($admin, 'admin')
            ->post($this->adminUrl('/agent-email-templates'), [
                'name' => 'Ogos 2026 Update',
                'recipient_scope' => AgentEmailTemplate::RecipientSelectedAgents,
                'agent_ids' => [$firstAgent->id, $secondAgent->id],
                'subject' => 'Pengumuman Ogos',
                'image_position' => AgentEmailTemplate::ImagePositionTop,
                'body' => "Assalamualaikum,\nAda pengumuman baru untuk semua ejen terpilih.",
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $template = AgentEmailTemplate::query()->where('name', 'Ogos 2026 Update')->firstOrFail();

        $this->assertSame(AgentEmailTemplate::RecipientSelectedAgents, $template->recipient_scope);
        $this->assertSame([$firstAgent->id, $secondAgent->id], $template->selectedAgentIds());
        $this->assertSame($admin->id, $template->created_by_admin_id);
        $this->assertSame(AgentEmailTemplate::ImagePositionTop, $template->image_position);
        Mail::assertNothingSent();
    }

    public function test_selected_agent_template_requires_at_least_one_recipient(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')
            ->from($this->adminUrl('/agent-email-templates/create'))
            ->post($this->adminUrl('/agent-email-templates'), [
                'name' => 'Empty selection',
                'recipient_scope' => AgentEmailTemplate::RecipientSelectedAgents,
                'subject' => 'Subjek',
                'image_position' => AgentEmailTemplate::ImagePositionTop,
                'body' => 'Kandungan',
            ])
            ->assertRedirect($this->adminUrl('/agent-email-templates/create'))
            ->assertSessionHasErrors('agent_ids');
    }

    public function test_admin_can_send_template_only_to_selected_agents_with_valid_email_addresses(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $selectedAgent = Agent::factory()->create(['email' => 'selected@example.com']);
        $invalidSelectedAgent = Agent::factory()->create(['email' => 'invalid-email']);
        $unselectedAgent = Agent::factory()->create(['email' => 'other@example.com']);

        $template = AgentEmailTemplate::query()->create([
            'name' => 'Selected Promo',
            'recipient_scope' => AgentEmailTemplate::RecipientSelectedAgents,
            'selected_agent_ids' => [$selectedAgent->id, $invalidSelectedAgent->id],
            'subject' => 'Promo khas',
            'body' => 'Ini promosi terbaru daripada Anugerah3D.',
            'created_by_admin_id' => $admin->id,
        ]);

        Mail::fake();

        $this->actingAs($admin, 'admin')
            ->post($this->adminUrl("/agent-email-templates/{$template->id}/send"))
            ->assertRedirect()
            ->assertSessionHas('success');

        $template->refresh();

        $this->assertNotNull($template->last_sent_at);
        $this->assertSame($admin->id, $template->last_sent_by_admin_id);

        Mail::assertSent(AgentTemplateMail::class, 1);
        Mail::assertSent(
            AgentTemplateMail::class,
            fn (AgentTemplateMail $mail): bool => $mail->recipient->is($selectedAgent)
                && $mail->hasTo('selected@example.com'),
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
            'name' => 'Branding Test',
            'recipient_scope' => AgentEmailTemplate::RecipientAllAgents,
            'subject' => 'Notis rasmi',
            'body' => "Baris pertama.\nBaris kedua.",
        ]);
        $agent = Agent::factory()->create(['agt_name' => 'Aisyah Agent']);

        $mail = new AgentTemplateMail($template, $agent);

        $mail->assertSeeInHtml('Anugerah3D');
        $mail->assertSeeInHtml('Notis rasmi');
        $mail->assertSeeInHtml('Hi Aisyah Agent');
        $mail->assertSeeInHtml('Baris pertama.');
        $mail->assertSeeInHtml('Baris kedua.');
    }

    private function adminUrl(string $path): string
    {
        return 'http://'.config('domains.admin').$path;
    }
}
