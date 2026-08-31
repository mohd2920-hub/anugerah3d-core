<?php

namespace Tests\Feature;

use App\Models\Agent;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AgentAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_agent_login_page_does_not_show_security_check(): void
    {
        $this->get($this->agentUrl('/login'))
            ->assertOk()
            ->assertViewIs('agent.auth.login')
            ->assertSeeText('Agent Login')
            ->assertDontSeeText('Security check')
            ->assertDontSee('name="captcha"', false);
    }

    public function test_active_agent_can_sign_in_without_security_check(): void
    {
        $agent = Agent::factory()->create();

        $this->post($this->agentUrl('/login'), [
            'login_id' => $agent->login_id,
            'password' => 'password',
        ])->assertRedirect(route('agent.dashboard'));

        $this->assertAuthenticatedAs($agent, 'agent');
        $this->assertNotNull($agent->refresh()->last_login_at);
        $this->assertSame('127.0.0.1', $agent->last_login_ip);
    }

    public function test_agent_cannot_sign_in_with_wrong_password(): void
    {
        $agent = Agent::factory()->create();

        $this->post($this->agentUrl('/login'), [
            'login_id' => $agent->login_id,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('login_id');

        $this->assertGuest('agent');
    }

    private function agentUrl(string $path): string
    {
        return 'http://'.config('domains.agent').$path;
    }
}
