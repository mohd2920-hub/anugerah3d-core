<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.profile.show', [
            'adminUser' => $request->user('admin'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->user('admin');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique($adminUser->getTable(), 'email')->ignore($adminUser->getKey()),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $adminUser->update($validated);

        AdminActivity::record(
            request: $request,
            event: 'admin.profile.updated',
            description: 'Admin profile updated.',
            adminUser: $adminUser,
            properties: ['page' => 'Profile'],
        );

        return redirect()
            ->route('admin.profile.show')
            ->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->user('admin');

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $adminUser->forceFill([
            'password' => $validated['password'],
        ])->save();

        AdminActivity::record(
            request: $request,
            event: 'admin.profile.password_changed',
            description: 'Admin changed their password from profile page.',
            adminUser: $adminUser,
            properties: ['page' => 'Profile'],
        );

        return redirect()
            ->route('admin.profile.show')
            ->with('success', 'Password changed successfully.');
    }
}
