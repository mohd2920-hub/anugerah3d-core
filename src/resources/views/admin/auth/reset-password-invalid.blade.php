@extends('admin.layouts.guest')

@section('title', 'Invalid Link | Anugerah3D Admin')

@section('content')
    <main class="min-h-screen bg-[#111827] px-5 py-8 sm:py-10">
        <section class="mx-auto grid min-h-[calc(100vh-5rem)] max-w-6xl overflow-hidden rounded-lg bg-white shadow-2xl shadow-black/30 lg:grid-cols-[0.95fr_1.05fr]">
            <div class="relative flex flex-col bg-[linear-gradient(145deg,#111827_0%,#172554_52%,#312e81_100%)] p-8 text-white sm:p-10 lg:p-12">
                <div class="absolute inset-x-0 top-0 h-1 bg-[linear-gradient(90deg,#f44336_0%,#f44336_34%,#f44336_67%,#f44336_100%)]"></div>

                <a href="{{ route('admin.login') }}" class="flex items-center gap-3" aria-label="Anugerah3D admin">
                    <span class="grid h-11 w-11 place-items-center rounded-lg bg-white text-sm font-bold text-[#111827] shadow-sm">A3D</span>
                    <span>
                        <span class="block text-sm font-semibold">Anugerah3D</span>
                        <span class="block text-xs text-blue-100/80">Admin Operations</span>
                    </span>
                </a>

                <div class="mt-16 max-w-md lg:mt-24">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-200">Invalid Link</p>
                    <h1 class="mt-4 text-4xl font-semibold leading-tight text-white sm:text-5xl">Link Expired</h1>
                    <p class="mt-5 text-base leading-8 text-blue-50/90">The password reset link is invalid or has expired. Please request a new one.</p>
                </div>

                <div class="mt-10 grid gap-3 text-sm text-blue-50/80 sm:grid-cols-3 lg:mt-auto">
                    <div class="rounded-lg border border-white/15 bg-white/[0.08] p-4 shadow-sm shadow-black/10">
                        <p class="font-semibold text-white">1 Hour</p>
                        <p class="mt-1 text-xs leading-5 text-blue-100/70">Expiry time</p>
                    </div>
                    <div class="rounded-lg border border-white/15 bg-white/[0.08] p-4 shadow-sm shadow-black/10">
                        <p class="font-semibold text-white">Security</p>
                        <p class="mt-1 text-xs leading-5 text-blue-100/70">Protected</p>
                    </div>
                    <div class="rounded-lg border border-white/15 bg-white/[0.08] p-4 shadow-sm shadow-black/10">
                        <p class="font-semibold text-white">Request New</p>
                        <p class="mt-1 text-xs leading-5 text-blue-100/70">Free reset</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center bg-white p-6 sm:p-10 lg:p-12">
                <div class="w-full max-w-md">
                    <div class="rounded-lg bg-red-50 p-6 border border-red-200">
                        <p class="text-sm font-semibold text-red-700">Password Reset Link Expired</p>
                        <p class="mt-2 text-sm text-red-600">This password reset link is either invalid or has expired. Links are valid for 1 hour.</p>
                    </div>

                    <div class="mt-6 space-y-3">
                        <a href="{{ route('admin.password.forgot') }}" class="inline-flex w-full min-h-11 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                            Request New Link
                        </a>
                        <a href="{{ route('admin.login') }}" class="inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                            Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
