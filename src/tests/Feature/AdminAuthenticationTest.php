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
        $admin = AdminUser::factory()->superAdmin()->create();

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
        $admin = AdminUser::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl('/login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_cannot_sign_in_with_wrong_password(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();

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
        $admin = AdminUser::factory()->superAdmin()->inactive()->create();

        $this->post($this->adminUrl('/login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_admin_can_sign_out(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();

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

    public function test_admin_can_sign_in_using_email_username_with_normalized_case_and_spaces(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create(['email' => 'posman@example.com']);
        $this->post($this->adminUrl('/login'), ['email' => '  POSMAN  ', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_duplicate_usernames_require_full_email_even_with_matching_password(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create(['email' => 'shared@example.com']);
        AdminUser::factory()->inactive()->create(['email' => 'shared@another.com', 'password' => 'different-secret']);
        $this->post($this->adminUrl('/login'), ['email' => 'shared', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $this->post($this->adminUrl('/login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_username_login_rejects_unknown_wrong_password_and_inactive_accounts(): void
    {
        AdminUser::factory()->superAdmin()->create(['email' => 'known@example.com']);
        AdminUser::factory()->inactive()->create(['email' => 'inactive@example.com']);
        foreach ([['unknown', 'password'], ['known', 'wrong-password'], ['inactive', 'password'], ['known%', 'password']] as [$identifier, $password]) {
            $this->post($this->adminUrl('/login'), ['email' => $identifier, 'password' => $password])->assertSessionHasErrors('email');
            $this->assertGuest('admin');
        }
    }

    public function test_username_does_not_bypass_pending_invitation(): void
    {
        $admin = AdminUser::factory()->create(['email' => 'invited@example.com']);
        $admin->forceFill(['invited_at' => now(), 'invitation_accepted_at' => null])->save();
        $this->post($this->adminUrl('/login'), ['email' => 'invited', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_login_field_accepts_usernames_on_mobile_and_rejects_array_input(): void
    {
        $this->get($this->adminUrl('/login'))->assertOk()
            ->assertSee('name="email" type="text"', false)
            ->assertSee('autocapitalize="none"', false)
            ->assertSeeText('Username or email');
        $this->post($this->adminUrl('/login'), ['email' => ['posman'], 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    private function adminUrl(string $path): string
    {
        return 'http://'.config('domains.admin').$path;
    }
}
