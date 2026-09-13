<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcceptStaffInvitationRequest;
use App\Models\AdminUser;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaffInvitationController extends Controller
{
    public function show(AdminUser $staff, string $token): View
    {
        $this->validateInvitation($staff, $token);

        return view('admin.staff.invitation', compact('staff', 'token'));
    }

    public function accept(AcceptStaffInvitationRequest $request, AdminUser $staff, string $token): RedirectResponse
    {
        DB::transaction(function () use ($request, $staff, $token): void {
            $staff = AdminUser::query()->lockForUpdate()->findOrFail($staff->id);
            $this->validateInvitation($staff, $token);
            $staff->forceFill([
                'password' => $request->validated('password'), 'email_verified_at' => now(),
                'invitation_accepted_at' => now(), 'invitation_token_hash' => null, 'invitation_expires_at' => null,
                'remember_token' => Str::random(60), 'access_version' => $staff->access_version + 1,
            ])->save();
            AdminActivity::record($request, 'admin.staff.invitation_accepted', 'Staff accepted their invitation.', $staff, ['staff_id' => $staff->id]);
        });

        return redirect()->route('admin.login')->with('status', 'Password set. You can now sign in with your email.');
    }

    private function validateInvitation(AdminUser $staff, string $token): void
    {
        abort_unless(! $staff->isSuperAdmin() && $staff->status === AdminUser::StatusActive && $staff->invitationPending()
            && $staff->invitation_expires_at?->isFuture() && $staff->invitation_token_hash
            && hash_equals($staff->invitation_token_hash, hash('sha256', $token)), 403, 'This invitation is invalid or expired. Ask your Superadmin for a new invitation.');
    }
}
