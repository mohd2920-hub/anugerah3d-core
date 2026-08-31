@extends('agent.layouts.guest')

@section('title', 'Agent Login | Anugerah3D')

@section('content')
<main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#17324d] px-4 py-8" style="padding-top: max(2rem, env(safe-area-inset-top)); padding-bottom: max(2rem, env(safe-area-inset-bottom));">
    <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-[#e7682b]/25 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-cyan-400/10 blur-3xl"></div>

    <section class="relative w-full max-w-md overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-slate-950/30">
        <div class="bg-[linear-gradient(135deg,#17324d_0%,#214866_100%)] px-6 pb-12 pt-7 text-white">
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-xs font-black tracking-tight text-[#17324d] shadow-lg">A3D</span>
                <div>
                    <p class="font-bold tracking-tight">Anugerah3D</p>
                    <p class="text-xs text-slate-300">Agent mobile workspace</p>
                </div>
            </div>
            <div class="mt-9">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-orange-300">Welcome back</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Agent Login</h1>
                <p class="mt-2 text-sm leading-6 text-slate-300">Access products, sales progress and your agent profile.</p>
            </div>
        </div>

        <div class="-mt-5 rounded-t-[2rem] bg-white px-6 pb-7 pt-7">
            <form method="post" action="{{ route('agent.login.store') }}" class="space-y-5">
                @csrf
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Login ID or phone number</span>
                    <span class="relative block">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
                        <input name="login_id" type="text" value="{{ old('login_id') }}" autocomplete="username" autocapitalize="off" inputmode="text" placeholder="e.g. agt1001 or 0132729040" required class="h-13 w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-base font-medium outline-none transition placeholder:normal-case placeholder:text-slate-400 focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                    </span>
                    @error('login_id')<span class="mt-2 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Password</span>
                    <span class="relative block">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <input id="agent-password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required class="h-13 w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-12 text-base outline-none transition placeholder:text-slate-400 focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                        <button type="button" data-password-toggle class="absolute right-2 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full text-slate-400 active:bg-slate-100" aria-label="Show password"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg></button>
                    </span>
                    @error('password')<span class="mt-2 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="flex min-h-10 items-center gap-3 text-sm font-medium text-slate-600">
                    <input name="remember" value="1" type="checkbox" @checked(old('remember')) class="h-5 w-5 rounded-md border-slate-300 text-[#e7682b] focus:ring-[#e7682b]">
                    Keep me signed in on this device
                </label>

                <button type="submit" class="flex h-13 w-full items-center justify-center gap-2 rounded-2xl bg-[#e7682b] px-5 text-sm font-extrabold text-white shadow-lg shadow-orange-600/20 transition active:scale-[0.99] active:bg-[#cf551e]">
                    Sign in to dashboard
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
                </button>
            </form>
            <p class="mt-6 text-center text-xs leading-5 text-slate-400">Only registered Anugerah3D agents can access this platform.</p>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
    document.querySelector('[data-password-toggle]')?.addEventListener('click', () => {
        const input = document.getElementById('agent-password');
        input.type = input.type === 'password' ? 'text' : 'password';
    });
</script>
@endpush
