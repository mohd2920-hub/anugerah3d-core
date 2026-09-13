<?php

namespace Tests\Feature\Feature\Admin;

use App\Models\AdminUser;
use App\Models\AgentEmailTemplate;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AgentEmailTemplateManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_invalid_image_position_does_not_create_template_or_send_email(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        Mail::fake();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.agent-email-templates.create'))
            ->post(route('admin.agent-email-templates.store'), [
                'name' => 'Invalid image placement',
                'recipient_scope' => AgentEmailTemplate::RecipientAllAgents,
                'subject' => 'Template validation',
                'body' => 'This template must not be saved.',
                'image_position' => 'unsupported',
            ])
            ->assertRedirect(route('admin.agent-email-templates.create'))
            ->assertSessionHasErrors(['image_position']);

        $this->assertDatabaseMissing('agent_email_templates', ['name' => 'Invalid image placement']);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }
}
