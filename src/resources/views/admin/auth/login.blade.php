@extends('admin.layouts.guest')

@section('title', 'Admin Login | Anugerah3D')

@section('content')
    <main class="min-h-screen bg-[#111827] px-5 py-8 sm:py-10">
        <section class="mx-auto grid min-h-[calc(100vh-5rem)] max-w-6xl overflow-hidden rounded-lg bg-white shadow-2xl shadow-black/30 lg:grid-cols-[0.95fr_1.05fr]">
            <div class="relative flex flex-col bg-[linear-gradient(145deg,#111827_0%,#172554_52%,#312e81_100%)] p-8 text-white sm:p-10 lg:p-12">
                <div class="absolute inset-x-0 top-0 h-1 bg-[linear-gradient(90deg,#4285f4_0%,#a142f4_34%,#fbbc04_67%,#34a853_100%)]"></div>

                <a href="{{ route('admin.login') }}" class="flex items-center gap-3" aria-label="Anugerah3D admin login">
                    <span class="grid h-11 w-11 place-items-center rounded-lg bg-white text-sm font-bold text-[#111827] shadow-sm">A3D</span>
                    <span>
                        <span class="block text-sm font-semibold">Anugerah3D</span>
                        <span class="block text-xs text-blue-100/80">Admin Operations</span>
                    </span>
                </a>

                <div class="mt-16 max-w-md lg:mt-24">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">Operations Command</p>
                    <h1 class="mt-4 text-4xl font-semibold leading-tight text-white sm:text-5xl">Admin Login</h1>
                    <p class="mt-5 text-base leading-8 text-blue-50/90">Manage orders, quotations, production queues, products, customers and agents from one workspace.</p>
                </div>

                <div class="mt-10 grid gap-3 text-sm text-blue-50/80 sm:grid-cols-3 lg:mt-auto">
                    <div class="rounded-lg border border-white/15 bg-white/[0.08] p-4 shadow-sm shadow-black/10">
                        <p class="font-semibold text-white">Orders</p>
                        <p class="mt-1 text-xs leading-5 text-blue-100/70">Daily flow</p>
                    </div>
                    <div class="rounded-lg border border-white/15 bg-white/[0.08] p-4 shadow-sm shadow-black/10">
                        <p class="font-semibold text-white">Production</p>
                        <p class="mt-1 text-xs leading-5 text-blue-100/70">Print queue</p>
                    </div>
                    <div class="rounded-lg border border-white/15 bg-white/[0.08] p-4 shadow-sm shadow-black/10">
                        <p class="font-semibold text-white">Customers</p>
                        <p class="mt-1 text-xs leading-5 text-blue-100/70">Profiles</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center bg-white p-6 sm:p-10 lg:p-12">
                <div class="w-full max-w-md">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#5f2eea]">Secure Area</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Sign in to dashboard</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Use your admin account to continue.</p>
                    </div>

                    <form method="post" action="{{ route('admin.login.store') }}" class="mt-8 grid gap-5">
                        @csrf

                        <label class="grid gap-2 text-sm font-medium text-slate-700">
                            Email
                            <input name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="admin@anugerah3d.com" class="min-h-11 rounded-lg border border-slate-300 bg-[#f8fafd] px-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:bg-white focus:ring-2 focus:ring-blue-100">
                            @error('email')
                                <span class="text-xs font-semibold text-red-600">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-slate-700">
                            Password
                            <input name="password" type="password" autocomplete="current-password" placeholder="Password" class="min-h-11 rounded-lg border border-slate-300 bg-[#f8fafd] px-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:bg-white focus:ring-2 focus:ring-blue-100">
                            @error('password')
                                <span class="text-xs font-semibold text-red-600">{{ $message }}</span>
                            @enderror
                        </label>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input name="remember" value="1" type="checkbox" @checked(old('remember')) class="h-4 w-4 rounded border-slate-300 text-[#1a73e8] focus:ring-[#1a73e8]">
                                Remember me
                            </label>

                            <a href="{{ route('admin.password.forgot') }}" class="text-sm font-semibold text-[#1a73e8] transition hover:text-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                                Forgot password?
                            </a>
                        </div>

                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                            Sign in
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>
@endsection
