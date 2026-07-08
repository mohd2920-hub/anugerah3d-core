<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Mail\Admin\PasswordResetMail;
use App\Models\AdminUser;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Show the forgot password form.
     */
    public function showForgotForm(): View
    {
        return view('admin.auth.forgot-password');
    }

    /**
     * Send password reset link to email.
     */
    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = $request->validated()['email'];

        $adminUser = AdminUser::where('email', $email)->first();
        if (! $adminUser) {
            AdminActivity::record(
                request: $request,
                event: 'admin.password_reset.requested',
                description: 'Password reset requested for an email that does not match an admin account.',
                properties: ['email' => $email, 'page' => 'Forgot Password'],
            );

            return back()->with('status', 'If an account exists with that email, a password reset link will be sent.');
        }

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $resetUrl = route('admin.password.reset', ['token' => $token, 'email' => $email]);
        Mail::to($email)->send(new PasswordResetMail($email, $resetUrl));

        AdminActivity::record(
            request: $request,
            event: 'admin.password_reset.link_sent',
            description: 'Password reset link sent to admin email.',
            adminUser: $adminUser,
            properties: ['email' => $email, 'page' => 'Forgot Password'],
        );

        return back()->with('status', 'Password reset link sent to your email.');
    }

    /**
     * Show the password reset form.
     */
    public function showResetForm(string $token, string $email): View
    {
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $token)
            ->where('created_at', '>', now()->subHour())
            ->first();

        if (! $passwordReset) {
            AdminActivity::record(
                request: request(),
                event: 'admin.password_reset.invalid_link',
                description: 'Invalid or expired password reset link opened.',
                adminUser: AdminUser::query()->where('email', $email)->first(),
                properties: ['email' => $email, 'page' => 'Password Reset'],
            );

            return view('admin.auth.reset-password-invalid');
        }

        return view('admin.auth.reset-password', compact('token', 'email'));
    }

    /**
     * Reset the password.
     */
    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $token = $validated['token'];
        $password = $validated['password'];

        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $token)
            ->where('created_at', '>', now()->subHour())
            ->first();

        if (! $passwordReset) {
            AdminActivity::record(
                request: $request,
                event: 'admin.password_reset.failed',
                description: 'Password reset failed because the token was invalid or expired.',
                adminUser: AdminUser::query()->where('email', $email)->first(),
                properties: ['email' => $email, 'page' => 'Password Reset'],
            );

            return redirect()->route('admin.password.forgot')->with('error', 'Invalid or expired reset token.');
        }

        $adminUser = AdminUser::where('email', $email)->first();
        if (! $adminUser) {
            AdminActivity::record(
                request: $request,
                event: 'admin.password_reset.failed',
                description: 'Password reset failed because the admin user was not found.',
                properties: ['email' => $email, 'page' => 'Password Reset'],
            );

            return redirect()->route('admin.password.forgot')->with('error', 'User not found.');
        }

        $adminUser->update(['password' => Hash::make($password)]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        AdminActivity::record(
            request: $request,
            event: 'admin.password_reset.completed',
            description: 'Admin password reset completed.',
            adminUser: $adminUser,
            properties: ['email' => $email, 'page' => 'Password Reset'],
        );

        return redirect()->route('admin.login')->with('status', 'Password reset successfully. Please log in with your new password.');
    }
}
