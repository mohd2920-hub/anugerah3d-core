<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = AdminUser::query()->find($request->user('admin')?->id);
        if (! $user || $user->status !== AdminUser::StatusActive || $user->invitationPending()) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors(['email' => 'This account is inactive or its invitation has not been accepted.']);
        }
        if (! $user->isSuperAdmin()) {
            $user->load('accessRoles');
        }
        Auth::guard('admin')->setUser($user);
        $permission = AdminAccess::requestPermission($request);
        abort_unless($user->isSuperAdmin() || $permission === 'self' || ($permission && AdminAccess::allows($user, $permission)), 403, 'You do not have permission to access this page or perform this action.');

        return $next($request);
    }
}
