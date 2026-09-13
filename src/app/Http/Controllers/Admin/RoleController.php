<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAdminRoleRequest;
use App\Models\AdminRole;
use App\Support\AdminAccess;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', ['roles' => AdminRole::query()->withCount('users')->orderBy('name')->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.roles.form', ['role' => new AdminRole(['permissions' => []]), 'modules' => AdminAccess::modules()]);
    }

    public function edit(AdminRole $role): View
    {
        return view('admin.roles.form', ['role' => $role->loadCount('users'), 'modules' => AdminAccess::modules()]);
    }

    public function store(SaveAdminRoleRequest $request): RedirectResponse
    {
        $role = DB::transaction(function () use ($request): AdminRole {
            $role = AdminRole::query()->create($request->safe()->only(['name', 'description', 'permissions']));
            AdminActivity::record($request, 'admin.role.created', 'Superadmin created an access role.', $request->user('admin'), ['before' => null, 'after' => $role->toArray()]);

            return $role;
        });

        return redirect()->route('admin.system.roles.edit', $role)->with('success', 'Role created.');
    }

    public function update(SaveAdminRoleRequest $request, AdminRole $role): RedirectResponse
    {
        DB::transaction(function () use ($request, $role): void {
            $role = AdminRole::query()->lockForUpdate()->findOrFail($role->id);
            if ($role->version !== (int) $request->validated('version')) {
                throw ValidationException::withMessages(['version' => 'This role changed. Reload before saving.']);
            }
            $before = $role->toArray();
            $role->update($request->safe()->only(['name', 'description', 'permissions']) + ['version' => $role->version + 1]);
            AdminActivity::record($request, 'admin.role.updated', 'Superadmin updated role permissions.', $request->user('admin'), ['before' => $before, 'after' => $role->toArray()]);
        });

        return redirect()->route('admin.system.roles.edit', $role)->with('success', 'Role updated for all assigned staff.');
    }

    public function destroy(Request $request, AdminRole $role): RedirectResponse
    {
        DB::transaction(function () use ($request, $role): void {
            $role = AdminRole::query()->lockForUpdate()->findOrFail($role->id);
            if ($role->users()->exists()) {
                throw ValidationException::withMessages(['role' => 'Reassign staff before deleting this role.']);
            }
            AdminActivity::record($request, 'admin.role.deleted', 'Superadmin deleted an unused role.', $request->user('admin'), ['before' => $role->toArray(), 'after' => null]);
            $role->delete();
        });

        return redirect()->route('admin.system.roles.index')->with('success', 'Unused role deleted.');
    }
}
