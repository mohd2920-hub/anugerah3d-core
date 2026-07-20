<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\BusinessSite;
use Database\Seeders\DataStateSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AgentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DataStateSeeder::class);
    }

    public function test_agents_index_requires_authentication(): void
    {
        $this->get($this->adminUrl('/agents'))
            ->assertRedirect('/login');
    }

    public function test_admin_can_view_agents_index(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create([
            'login_id' => 'AGT-TEST-001',
            'agt_name' => 'Aisyah Agent',
            'phone_number' => '0123456789',
        ]);

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl('/agents'))
            ->assertOk()
            ->assertViewIs('admin.agents.index')
            ->assertSeeText('Agents')
            ->assertSeeText($agent->login_id)
            ->assertSeeText($agent->agt_name)
            ->assertSee('https://wa.me/60123456789', false);
    }

    public function test_admin_can_view_agent_edit_form(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl("/agents/{$agent->id}/edit"))
            ->assertOk()
            ->assertViewIs('admin.agents.edit')
            ->assertViewHas('businessSites')
            ->assertSeeText('Assigned business sites')
            ->assertSeeText('Clear all');
    }

    public function test_admin_can_search_agents(): void
    {
        $admin = AdminUser::factory()->create();
        Agent::factory()->create([
            'login_id' => 'AGT-KEY-001',
            'agt_name' => 'Searchable Agent',
        ]);
        Agent::factory()->create([
            'login_id' => 'AGT-OTHER-002',
            'agt_name' => 'Other Agent',
        ]);

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl('/agents?search=KEY'))
            ->assertOk()
            ->assertSeeText('AGT-KEY-001')
            ->assertDontSeeText('AGT-OTHER-002');
    }

    public function test_admin_can_create_agent(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post($this->adminUrl('/agents'), $this->validPayload())
            ->assertRedirect(route('admin.agents.index'))
            ->assertSessionHas('agent_login_info');

        $this->assertDatabaseHas('usr_agent', [
            'login_id' => 'AGT-TEST-001',
            'agt_name' => 'Aisyah Agent',
            'email' => 'agent@example.com',
            'state' => 'Selangor',
        ]);

        $agent = Agent::query()->where('login_id', 'AGT-TEST-001')->firstOrFail();

        $this->assertTrue(Hash::check('AgentPass123', $agent->password));
    }

    public function test_agent_login_id_must_be_unique(): void
    {
        $admin = AdminUser::factory()->create();
        Agent::factory()->create(['login_id' => 'AGT-TEST-001']);

        $this->actingAs($admin, 'admin')
            ->from($this->adminUrl('/agents/create'))
            ->post($this->adminUrl('/agents'), $this->validPayload())
            ->assertRedirect($this->adminUrl('/agents/create'))
            ->assertSessionHasErrors('login_id');
    }

    public function test_admin_can_update_agent(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create([
            'login_id' => 'AGT-OLD-001',
            'agt_name' => 'Old Agent',
        ]);

        $this->actingAs($admin, 'admin')
            ->put($this->adminUrl("/agents/{$agent->id}"), $this->validPayload([
                'login_id' => 'AGT-NEW-002',
                'agt_name' => 'Updated Agent',
                'email' => 'updated-agent@example.com',
                'id_number' => '910101-10-3333',
                'agt_status' => Agent::StatusSuspended,
                'password' => null,
                'password_confirmation' => null,
            ]))
            ->assertRedirect(route('admin.agents.index'));

        $this->assertDatabaseHas('usr_agent', [
            'id' => $agent->id,
            'login_id' => 'AGT-NEW-002',
            'agt_name' => 'Updated Agent',
            'email' => 'updated-agent@example.com',
            'agt_status' => Agent::StatusSuspended,
        ]);
    }

    public function test_admin_can_clear_all_agent_business_sites(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create();
        $businessSite = BusinessSite::query()->create([
            'site_name' => 'Test Site',
            'city' => 'Bangi',
        ]);
        $agent->businessSites()->attach($businessSite);

        $this->actingAs($admin, 'admin')
            ->put($this->adminUrl("/agents/{$agent->id}"), $this->validPayload([
                'login_id' => 'AGT-CLEAR-001',
                'email' => 'clear-sites@example.com',
            ]))
            ->assertRedirect(route('admin.agents.index'));

        $this->assertDatabaseMissing('agent_business_site', [
            'agent_id' => $agent->id,
            'business_site_id' => $businessSite->id,
        ]);
    }

    public function test_admin_can_reset_agent_password(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create();

        $this->actingAs($admin, 'admin')
            ->put($this->adminUrl("/agents/{$agent->id}/password"), [
                'password' => 'NewAgentPass123',
                'password_confirmation' => 'NewAgentPass123',
            ])
            ->assertRedirect(route('admin.agents.edit', $agent))
            ->assertSessionHas('agent_login_info');

        $agent->refresh();

        $this->assertTrue(Hash::check('NewAgentPass123', $agent->password));
    }

    public function test_admin_can_upload_agent_profile_picture_as_300px_thumb(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create(['profile_picture' => null]);

        $this->actingAs($admin, 'admin')
            ->put($this->adminUrl("/agents/{$agent->id}/profile-picture"), [
                'profile_picture_file' => UploadedFile::fake()->image('agent-photo.jpg', 900, 600),
            ])
            ->assertRedirect(route('admin.agents.edit', $agent));

        $agent->refresh();

        $this->assertNotNull($agent->profile_picture);
        $this->assertStringStartsWith('profiles/agent-'.$agent->id.'-', $agent->profile_picture);
        $this->assertStringEndsWith('.jpg', $agent->profile_picture);

        $absolutePath = public_path($agent->profile_picture);

        $this->assertFileExists($absolutePath);

        [$width, $height] = getimagesize($absolutePath);

        $this->assertSame(300, $width);
        $this->assertSame(300, $height);

        unlink($absolutePath);
    }

    public function test_admin_can_delete_agent(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create();

        $this->actingAs($admin, 'admin')
            ->delete($this->adminUrl("/agents/{$agent->id}"), [
                'delete_password' => 'password',
            ])
            ->assertRedirect(route('admin.agents.index'));

        $this->assertDatabaseMissing('usr_agent', [
            'id' => $agent->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'login_id' => 'AGT-TEST-001',
            'agt_name' => 'Aisyah Agent',
            'id_number' => '900101-10-1234',
            'email' => 'agent@example.com',
            'phone_number' => '0123456789',
            'password' => 'AgentPass123',
            'password_confirmation' => 'AgentPass123',
            'agt_status' => Agent::StatusActive,
            'address' => '12 Jalan Mawar',
            'city' => 'Shah Alam',
            'state' => 'Selangor',
            'discount_percentage' => 12.5,
            'profile_picture' => 'agents/aisyah.jpg',
            'total_sale' => 1250.75,
        ], array_filter($overrides, static fn ($value): bool => $value !== null));
    }

    private function adminUrl(string $path): string
    {
        return 'http://'.config('domains.admin').$path;
    }
}
