<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#17324d">
    <meta name="description" content="Anugerah3D agent mobile platform">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/agent-app.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icons/agent-app.svg">
    <title>@yield('title', 'Anugerah3D Agent')</title>
    @fonts
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-[#eef3f6] text-slate-950 antialiased selection:bg-orange-200 selection:text-orange-950">
    <div class="mx-auto min-h-screen max-w-xl bg-[#f7f9fa] shadow-[0_0_50px_rgba(15,23,42,0.08)]">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 px-4 backdrop-blur" style="padding-top: env(safe-area-inset-top);">
            <div class="flex h-16 items-center gap-3">
                @hasSection('back_url')
                    <a href="@yield('back_url')" class="grid h-10 w-10 flex-none place-items-center rounded-full text-slate-700 transition active:bg-slate-100" aria-label="Go back">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                    </a>
                @else
                    <span class="grid h-10 w-10 flex-none place-items-center rounded-xl bg-[#17324d] text-[11px] font-extrabold tracking-tight text-white">A3D</span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[11px] font-semibold uppercase tracking-[0.16em] text-[#e7682b]">Agent workspace</p>
                    <h1 class="truncate text-lg font-bold tracking-tight text-[#17324d]">@yield('page_title', 'Dashboard')</h1>
                </div>
                <button type="button" data-install-app class="hidden h-10 items-center gap-2 rounded-full bg-orange-50 px-3 text-xs font-bold text-[#d95419]" aria-label="Install app">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                    Install
                </button>
                <a href="{{ route('agent.profile') }}" class="grid h-10 w-10 flex-none place-items-center overflow-hidden rounded-full bg-orange-100 text-xs font-extrabold text-[#d95419] ring-2 ring-white" aria-label="Open profile">
                    @if (auth('agent')->user()?->profile_picture)
                        <img src="{{ filter_var(auth('agent')->user()->profile_picture, FILTER_VALIDATE_URL) ? auth('agent')->user()->profile_picture : asset(auth('agent')->user()->profile_picture) }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ auth('agent')->user()?->initials() }}
                    @endif
                </a>
            </div>
        </header>

        <div data-offline-banner class="hidden bg-amber-100 px-4 py-2 text-center text-xs font-semibold text-amber-900">You are offline. Some information may be unavailable.</div>

        <main class="px-4 pb-28 pt-5 sm:px-5">
            @yield('content')
        </main>

        <x-agent.bottom-navigation />
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
        }

        const offlineBanner = document.querySelector('[data-offline-banner]');
        const updateConnection = () => offlineBanner?.classList.toggle('hidden', navigator.onLine);
        window.addEventListener('online', updateConnection);
        window.addEventListener('offline', updateConnection);
        updateConnection();

        let installPrompt;
        const installButton = document.querySelector('[data-install-app]');
        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            installPrompt = event;
            installButton?.classList.remove('hidden');
            installButton?.classList.add('inline-flex');
        });
        installButton?.addEventListener('click', async () => {
            if (!installPrompt) return;
            installPrompt.prompt();
            await installPrompt.userChoice;
            installPrompt = null;
            installButton.classList.add('hidden');
        });
    </script>
    @stack('scripts')
</body>
</html>
