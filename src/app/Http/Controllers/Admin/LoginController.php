<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\ActivityLog;
use App\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Display the admin login page.
     */
    public function __invoke(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Authenticate an admin user.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->credentials();

        if (! Auth::guard('admin')->attempt($credentials, $request->remember())) {
            $this->recordLoginActivity(
                request: $request,
                event: 'admin.login.failed',
                description: 'Admin login failed.',
                adminUser: AdminUser::query()->where('email', $credentials['email'])->first(),
                properties: ['email' => $credentials['email']],
            );

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ])->redirectTo(route('admin.login'));
        }

        $request->session()->regenerate();

        /** @var AdminUser|null $adminUser */
        $adminUser = Auth::guard('admin')->user();

        $adminUser?->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $this->recordLoginActivity(
            request: $request,
            event: 'admin.login.succeeded',
            description: 'Admin login succeeded.',
            adminUser: $adminUser,
        );

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * End the active admin session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function recordLoginActivity(
        Request $request,
        string $event,
        string $description,
        ?AdminUser $adminUser,
        array $properties = [],
    ): void {
        ActivityLog::query()->create([
            'admin_user_id' => $adminUser?->getKey(),
            'event' => $event,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'properties' => $properties === [] ? null : $properties,
        ]);
    }
}
