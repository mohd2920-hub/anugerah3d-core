@extends('admin.layouts.app')

@section('title', 'Profile | Anugerah3D Admin')

@section('page_title', 'Profile')

@section('content')
    @php
        $nameParts = preg_split('/\s+/', trim((string) $adminUser->name)) ?: [];
        $initials = collect($nameParts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->implode('') ?: 'AD';
        $lastLogin = $adminUser->last_login_at?->format('d M Y, h:i A') ?: '-';
        $verifiedAt = $adminUser->email_verified_at?->format('d M Y, h:i A') ?: '-';
        $showProfileForm = $errors->hasAny(['name', 'email', 'phone']);
        $showPasswordForm = $errors->hasAny(['current_password', 'password']);
    @endphp

    <div class="mx-auto max-w-5xl space-y-5">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="grid h-16 w-16 flex-none place-items-center rounded-lg bg-blue-50 text-xl font-bold text-[#1a73e8] ring-1 ring-blue-100">
                        {{ $initials }}
                    </div>
                    <div class="min-w-0">
                        <h1 class="break-words text-2xl font-bold text-slate-950">{{ $adminUser->name }}</h1>
                        <p class="mt-1 break-all text-sm text-slate-600">{{ $adminUser->email }}</p>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                            <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-slate-700">{{ ucfirst(str_replace('_', ' ', $adminUser->role)) }}</span>
                            <span class="rounded-lg bg-green-50 px-2.5 py-1 text-green-700">{{ ucfirst($adminUser->status) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" data-profile-toggle="edit-profile-form" class="inline-flex min-h-8 items-center justify-center rounded-md border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2" aria-expanded="{{ $showProfileForm ? 'true' : 'false' }}">
                        Edit
                    </button>
                    <button type="button" data-profile-toggle="change-password-form" class="inline-flex min-h-8 items-center justify-center rounded-md border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2" aria-expanded="{{ $showPasswordForm ? 'true' : 'false' }}">
                        Change password
                    </button>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex min-h-8 items-center justify-center rounded-md border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2">
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>

            <dl class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">Phone</dt>
                    <dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $adminUser->phone ?: '-' }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">Last Login</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $lastLogin }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">Last IP</dt>
                    <dd class="mt-1 break-all text-sm font-semibold text-slate-900">{{ $adminUser->last_login_ip ?: '-' }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">Verified</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $verifiedAt }}</dd>
                </div>
            </dl>
        </section>

        <section id="edit-profile-form" class="{{ $showProfileForm ? '' : 'hidden' }} rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <div class="mb-5">
                <h2 class="text-xl font-semibold text-slate-950">Edit Profile</h2>
            </div>

            <form method="POST" action="{{ route('admin.profile.update') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $adminUser->name) }}" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error('name')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $adminUser->email) }}" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error('email')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="mb-2 block text-sm font-medium text-slate-700">Phone</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone', $adminUser->phone) }}" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    @error('phone')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex items-end">
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-5 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                            Save Profile
                        </button>
                        <button type="button" data-profile-close="edit-profile-form" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                            Cancel
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <section id="change-password-form" class="{{ $showPasswordForm ? '' : 'hidden' }} rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <div class="mb-5">
                <h2 class="text-xl font-semibold text-slate-950">Change Password</h2>
            </div>

            <form method="POST" action="{{ route('admin.profile.password.update') }}" class="grid gap-4 md:grid-cols-3">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="mb-2 block text-sm font-medium text-slate-700">Current Password</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error('current_password')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-700">New Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error('password')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">Confirm New Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                </div>

                <div class="md:col-span-3">
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                            Update Password
                        </button>
                        <button type="button" data-profile-close="change-password-form" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                            Cancel
                        </button>
                    </div>
                </div>
            </form>
        </section>
    </div>

    <script>
        (function () {
            function setPanel(panelId, shouldShow) {
                const panel = document.getElementById(panelId);
                const toggle = document.querySelector('[data-profile-toggle="' + panelId + '"]');

                if (! panel) {
                    return;
                }

                panel.classList.toggle('hidden', ! shouldShow);

                if (toggle) {
                    toggle.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');
                }

                if (shouldShow) {
                    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }

            document.querySelectorAll('[data-profile-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const panelId = button.dataset.profileToggle;
                    const panel = document.getElementById(panelId);

                    if (panel) {
                        setPanel(panelId, panel.classList.contains('hidden'));
                    }
                });
            });

            document.querySelectorAll('[data-profile-close]').forEach(function (button) {
                button.addEventListener('click', function () {
                    setPanel(button.dataset.profileClose, false);
                });
            });
        })();
    </script>
@endsection
