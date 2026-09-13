<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\InviteStaff;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveStaffRequest;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Support\AdminAccess;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', 'in:active,inactive'], 'role_id' => ['nullable', 'integer', 'exists:admin_roles,id']]);
        $staff = AdminUser::query()->with('accessRoles')->when($filters['search'] ?? null, function ($query, string $search): void {
            $query->where(fn ($query) => $query->where('name', 'like', "%$search%")->orWhere('email', 'like', "%$search%"));
        })->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['role_id'] ?? null, fn ($query, $role) => $query->whereHas('accessRoles', fn ($query) => $query->whereKey($role)))
            ->latest('id')->paginate(20)->withQueryString();

        return view('admin.staff.index', ['staff' => $staff, 'roles' => AdminRole::query()->orderBy('name')->get(), 'filters' => $filters]);
    }

    public function create(): View
    {
        return $this->form(new AdminUser);
    }

    public function edit(AdminUser $staff): View
    {
        abort_if($staff->isSuperAdmin(), 403, 'Superadmin accounts are protected. Use your own Profile to change personal details.');

        return $this->form($staff->load('accessRoles'));
    }

    public function store(SaveStaffRequest $request, InviteStaff $invite): RedirectResponse
    {
        $staff = DB::transaction(function () use ($request): AdminUser {
            $data = $request->validated();
            $staff = AdminUser::query()->create([
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null,
                'status' => $data['status'], 'role' => AdminUser::RoleAdmin, 'password' => Str::random(64),
            ]);
            $staff->forceFill(['invited_at' => now()])->save();
            $staff->accessRoles()->sync($data['roles']);
            AdminActivity::record($request, 'admin.staff.created', 'Superadmin created a staff account.', $request->user('admin'), ['before' => null, 'after' => $this->snapshot($staff)]);

            return $staff;
        });
        $sent = $staff->status === AdminUser::StatusActive && $invite->handle($request, $staff);

        return redirect()->route('admin.system.staff.edit', $staff)->with($sent ? 'success' : 'warning', $sent ? 'Staff created. Invitation sent.' : 'Staff saved. Invitation not sent. Activate the account and use Resend Invitation when ready.');
    }

    public function update(SaveStaffRequest $request, AdminUser $staff, InviteStaff $invite): RedirectResponse
    {
        $emailChanged = DB::transaction(function () use ($request, $staff): bool {
            $staff = AdminUser::query()->lockForUpdate()->findOrFail($staff->id);
            abort_if($staff->isSuperAdmin(), 403, 'Superadmin accounts cannot be changed through User Management.');
            $data = $request->validated();
            if ($staff->access_version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'This account changed. Reload before saving.']);
            }
            $before = $this->snapshot($staff);
            $emailChanged = $staff->email !== $data['email'];
            $staff->fill(collect($data)->only(['name', 'email', 'phone', 'status'])->all());
            $staff->forceFill(['access_version' => $staff->access_version + 1]);
            if ($emailChanged) {
                $staff->forceFill(['invited_at' => now(), 'invitation_accepted_at' => null, 'email_verified_at' => null, 'password' => Str::random(64)]);
            }
            if ($emailChanged || $data['status'] === AdminUser::StatusInactive) {
                $staff->forceFill(['remember_token' => Str::random(60), 'invitation_token_hash' => null, 'invitation_expires_at' => null]);
            }
            $staff->save();
            $staff->accessRoles()->sync($data['roles']);
            AdminActivity::record($request, 'admin.staff.updated', 'Superadmin updated staff access.', $request->user('admin'), ['before' => $before, 'after' => $this->snapshot($staff->fresh())]);

            return $emailChanged;
        });
        $staff->refresh();
        if ($emailChanged && $staff->status === AdminUser::StatusActive && ! $invite->handle($request, $staff)) {
            return back()->with('warning', 'Staff saved, but invitation could not be sent. Use Resend Invitation.');
        }

        return redirect()->route('admin.system.staff.edit', $staff)->with('success', 'Staff updated. Access changes apply on the next request.');
    }

    public function resend(Request $request, AdminUser $staff, InviteStaff $invite): RedirectResponse
    {
        $sent = $invite->handle($request, $staff);

        return back()->with($sent ? 'success' : 'warning', $sent ? 'Invitation sent. Previous invitation links are no longer valid.' : 'Invitation could not be sent. Please try again.');
    }

    private function form(AdminUser $staff): View
    {
        return view('admin.staff.form', ['staff' => $staff, 'roles' => AdminRole::query()->orderBy('name')->get(), 'modules' => AdminAccess::modules()]);
    }

    private function snapshot(AdminUser $staff): array
    {
        return ['id' => $staff->id, 'name' => $staff->name, 'email' => $staff->email, 'phone' => $staff->phone, 'status' => $staff->status, 'roles' => $staff->accessRoles()->orderBy('admin_roles.id')->get(['admin_roles.id', 'name'])->toArray()];
    }
}
