<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Mail\Admin\PasswordResetMail;
use App\Models\AdminUser;
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

        // Check if admin user exists
        $adminUser = AdminUser::where('email', $email)->first();
        if (! $adminUser) {
            return back()->with('status', 'If an account exists with that email, a password reset link will be sent.');
        }

        // Delete existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Create new reset token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => now(),
        ]);

        // Send email with reset link
        $resetUrl = route('admin.password.reset', ['token' => $token, 'email' => $email]);
        Mail::to($email)->send(new PasswordResetMail($email, $resetUrl));

        return back()->with('status', 'Password reset link sent to your email.');
    }

    /**
     * Show the password reset form.
     */
    public function showResetForm(string $token, string $email): View
    {
        // Verify token exists and is not expired (1 hour expiry)
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $token)
            ->where('created_at', '>', now()->subHour())
            ->first();

        if (! $passwordReset) {
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

        // Verify token exists and is not expired
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $token)
            ->where('created_at', '>', now()->subHour())
            ->first();

        if (! $passwordReset) {
            return redirect()->route('admin.password.forgot')->with('error', 'Invalid or expired reset token.');
        }

        // Find and update admin user
        $adminUser = AdminUser::where('email', $email)->first();
        if (! $adminUser) {
            return redirect()->route('admin.password.forgot')->with('error', 'User not found.');
        }

        $adminUser->update(['password' => Hash::make($password)]);

        // Delete the used token
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return redirect()->route('admin.login')->with('status', 'Password reset successfully. Please log in with your new password.');
    }
}
