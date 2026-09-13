<?php

namespace App\Actions\Admin;

use App\Mail\Admin\StaffInvitationMail;
use App\Models\AdminUser;
use App\Support\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class InviteStaff
{
    public function handle(Request $request, AdminUser $staff): bool
    {
        abort_unless($request->user('admin')?->isSuperAdmin(), 403);
        $token = Str::random(64);
        $staff = DB::transaction(function () use ($staff, $token): AdminUser {
            $staff = AdminUser::query()->lockForUpdate()->findOrFail($staff->id);
            abort_if($staff->isSuperAdmin() || $staff->status !== AdminUser::StatusActive || ! $staff->invitationPending(), 403, 'Only active staff with a pending invitation can be invited.');
            $staff->forceFill(['invitation_token_hash' => hash('sha256', $token), 'invitation_expires_at' => now()->addHours(48)])->save();

            return $staff;
        });
        try {
            Mail::to($staff->email)->send(new StaffInvitationMail($staff->name, route('admin.invitation.show', ['staff' => $staff, 'token' => $token])));
        } catch (Throwable $exception) {
            report($exception);
            AdminActivity::record($request, 'admin.staff.invitation_failed', 'Staff invitation could not be sent.', $request->user('admin'), ['staff_id' => $staff->id]);

            return false;
        }
        AdminActivity::record($request, 'admin.staff.invited', 'Superadmin sent a staff invitation.', $request->user('admin'), ['staff_id' => $staff->id, 'email' => $staff->email]);

        return true;
    }
}
