<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = AdminUser::factory()->create([
            'email' => 'admin@example.com',
        ]);
    }

    public function test_can_view_forgot_password_form(): void
    {
        $response = $this->get(route('admin.password.forgot'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.auth.forgot-password');
    }

    public function test_can_request_password_reset_with_valid_email(): void
    {
        Mail::fake();

        $response = $this->post(route('admin.password.email'), [
            'email' => $this->adminUser->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Check that token was created in database
        $this->assertTrue(
            DB::table('password_reset_tokens')
                ->where('email', $this->adminUser->email)
                ->exists()
        );
    }

    public function test_cannot_request_password_reset_with_invalid_email(): void
    {
        $response = $this->post(route('admin.password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertRedirect();
        // Should still show success message for security reasons
        $response->assertSessionHas('status');

        // But no token should be created
        $this->assertFalse(
            DB::table('password_reset_tokens')
                ->where('email', 'nonexistent@example.com')
                ->exists()
        );
    }

    public function test_requires_email_for_password_reset(): void
    {
        $response = $this->post(route('admin.password.email'), [
            'email' => '',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_can_view_reset_password_form_with_valid_token(): void
    {
        // Create reset token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $this->adminUser->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $response = $this->get(route('admin.password.reset', [
            'token' => $token,
            'email' => $this->adminUser->email,
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.auth.reset-password');
        $response->assertViewHas('token', $token);
        $response->assertViewHas('email', $this->adminUser->email);
    }

    public function test_cannot_view_reset_password_form_with_expired_token(): void
    {
        // Create expired reset token (2 hours old)
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $this->adminUser->email,
            'token' => $token,
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->get(route('admin.password.reset', [
            'token' => $token,
            'email' => $this->adminUser->email,
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.auth.reset-password-invalid');
    }

    public function test_cannot_view_reset_password_form_with_invalid_token(): void
    {
        $response = $this->get(route('admin.password.reset', [
            'token' => 'invalid-token',
            'email' => $this->adminUser->email,
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.auth.reset-password-invalid');
    }

    public function test_can_reset_password_with_valid_token(): void
    {
        // Create reset token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $this->adminUser->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $response = $this->post(route('admin.password.update'), [
            'email' => $this->adminUser->email,
            'token' => $token,
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHas('status');

        // Verify password was updated
        $this->assertTrue(
            Hash::check(
                'newPassword123',
                $this->adminUser->fresh()->password
            )
        );

        // Verify token was deleted
        $this->assertFalse(
            DB::table('password_reset_tokens')
                ->where('email', $this->adminUser->email)
                ->exists()
        );
    }

    public function test_cannot_reset_password_with_mismatched_passwords(): void
    {
        // Create reset token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $this->adminUser->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $response = $this->post(route('admin.password.update'), [
            'email' => $this->adminUser->email,
            'token' => $token,
            'password' => 'newPassword123',
            'password_confirmation' => 'differentPassword',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_requires_minimum_password_length(): void
    {
        // Create reset token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $this->adminUser->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $response = $this->post(route('admin.password.update'), [
            'email' => $this->adminUser->email,
            'token' => $token,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_cannot_reset_password_with_expired_token(): void
    {
        // Create expired reset token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $this->adminUser->email,
            'token' => $token,
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->post(route('admin.password.update'), [
            'email' => $this->adminUser->email,
            'token' => $token,
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ]);

        $response->assertRedirect(route('admin.password.forgot'));
        $response->assertSessionHas('error');
    }
}
