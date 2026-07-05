@extends('admin.layouts.guest')

@section('title', 'Reset Password | Anugerah3D Admin')

@section('content')
    <main class="min-h-screen bg-[#111827] px-5 py-8 sm:py-10">
        <section class="mx-auto grid min-h-[calc(100vh-5rem)] max-w-6xl overflow-hidden rounded-lg bg-white shadow-2xl shadow-black/30 lg:grid-cols-[0.95fr_1.05fr]">
            <div class="relative flex flex-col bg-[linear-gradient(145deg,#111827_0%,#172554_52%,#312e81_100%)] p-8 text-white sm:p-10 lg:p-12">
                <div class="absolute inset-x-0 top-0 h-1 bg-[linear-gradient(90deg,#4285f4_0%,#a142f4_34%,#fbbc04_67%,#34a853_100%)]"></div>

                <a href="{{ route('admin.login') }}" class="flex items-center gap-3" aria-label="Anugerah3D admin">
                    <span class="grid h-11 w-11 place-items-center rounded-lg bg-white text-sm font-bold text-[#111827] shadow-sm">A3D</span>
                    <span>
                        <span class="block text-sm font-semibold">Anugerah3D</span>
                        <span class="block text-xs text-blue-100/80">Admin Operations</span>
                    </span>
                </a>

                <div class="mt-16 max-w-md lg:mt-24">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">Set New Password</p>
                    <h1 class="mt-4 text-4xl font-semibold leading-tight text-white sm:text-5xl">Create New Password</h1>
                    <p class="mt-5 text-base leading-8 text-blue-50/90">Enter your new password below. Make sure it's secure and at least 8 characters long.</p>
                </div>

                <div class="mt-10 grid gap-3 text-sm text-blue-50/80 sm:grid-cols-3 lg:mt-auto">
                    <div class="rounded-lg border border-white/15 bg-white/[0.08] p-4 shadow-sm shadow-black/10">
                        <p class="font-semibold text-white">Strong</p>
                        <p class="mt-1 text-xs leading-5 text-blue-100/70">8+ characters</p>
                    </div>
                    <div class="rounded-lg border border-white/15 bg-white/[0.08] p-4 shadow-sm shadow-black/10">
                        <p class="font-semibold text-white">Secure</p>
                        <p class="mt-1 text-xs leading-5 text-blue-100/70">Encrypted</p>
                    </div>
                    <div class="rounded-lg border border-white/15 bg-white/[0.08] p-4 shadow-sm shadow-black/10">
                        <p class="font-semibold text-white">Verified</p>
                        <p class="mt-1 text-xs leading-5 text-blue-100/70">Confirmed match</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center bg-white p-6 sm:p-10 lg:p-12">
                <div class="w-full max-w-md">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#5f2eea]">New Password</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Reset your password</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Enter a new strong password for your admin account.</p>
                    </div>

                    <form method="post" action="{{ route('admin.password.update') }}" class="mt-8 grid gap-5">
                        @csrf

                        <input type="hidden" name="email" value="{{ $email }}">
                        <input type="hidden" name="token" value="{{ $token }}">

                        <label class="grid gap-2 text-sm font-medium text-slate-700">
                            New Password
                            <input name="password" type="password" autocomplete="new-password" placeholder="Enter your new password" class="min-h-11 rounded-lg border border-slate-300 bg-[#f8fafd] px-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:bg-white focus:ring-2 focus:ring-blue-100">
                            @error('password')
                                <span class="text-xs font-semibold text-red-600">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-slate-700">
                            Confirm Password
                            <input name="password_confirmation" type="password" autocomplete="new-password" placeholder="Confirm your password" class="min-h-11 rounded-lg border border-slate-300 bg-[#f8fafd] px-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:bg-white focus:ring-2 focus:ring-blue-100">
                            @error('password_confirmation')
                                <span class="text-xs font-semibold text-red-600">{{ $message }}</span>
                            @enderror
                        </label>

                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                            Reset Password
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-sm text-slate-600">
                            <a href="{{ route('admin.login') }}" class="font-semibold text-[#1a73e8] hover:text-[#1558b0]">Back to login</a>
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
