<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_dashboard_requires_authentication(): void
    {
        $this->get($this->adminUrl('/dashboard'))
            ->assertRedirect('/login');
    }

    public function test_admin_login_page_is_available_without_reset_or_captcha(): void
    {
        $this->get($this->adminUrl('/login'))
            ->assertOk()
            ->assertViewIs('admin.auth.login')
            ->assertSeeText('Admin Login')
            ->assertSeeText('Sign in')
            ->assertSeeText('Forgot password?')
            ->assertDontSeeText('Security Check');
    }

    public function test_active_admin_can_sign_in(): void
    {
        $admin = AdminUser::factory()->create();

        $this->post($this->adminUrl('/login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotNull($admin->refresh()->last_login_at);
        $this->assertSame('127.0.0.1', $admin->last_login_ip);
        $this->assertDatabaseHas('activity_logs', [
            'admin_user_id' => $admin->id,
            'event' => 'admin.login.succeeded',
        ]);
    }

    public function test_authenticated_admin_is_redirected_away_from_login(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl('/login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_cannot_sign_in_with_wrong_password(): void
    {
        $admin = AdminUser::factory()->create();

        $this->post($this->adminUrl('/login'), [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
        $this->assertDatabaseHas('activity_logs', [
            'admin_user_id' => $admin->id,
            'event' => 'admin.login.failed',
        ]);
    }

    public function test_inactive_admin_cannot_sign_in(): void
    {
        $admin = AdminUser::factory()->inactive()->create();

        $this->post($this->adminUrl('/login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_admin_can_sign_out(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post($this->adminUrl('/logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }

    public function test_seeded_super_admin_can_sign_in(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = AdminUser::query()
            ->where('email', 'anugerah3d@gmail.com')
            ->firstOrFail();

        $this->assertSame('Mohamad', $admin->name);
        $this->assertSame(AdminUser::RoleSuperAdmin, $admin->role);
        $this->assertTrue(Hash::check('012345678*', (string) $admin->password));

        $this->post($this->adminUrl('/login'), [
            'email' => $admin->email,
            'password' => '012345678*',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    private function adminUrl(string $path): string
    {
        return 'http://'.config('domains.admin').$path;
    }
}
