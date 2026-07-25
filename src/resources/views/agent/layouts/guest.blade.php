<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#17324d">
    <meta name="description" content="Anugerah3D agent platform">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/agent-app-64.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/agent-app-192.png">
    <title>@yield('title', 'Anugerah3D Agent')</title>
    @fonts
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-[#eef3f6] text-slate-950 antialiased selection:bg-orange-200 selection:text-orange-950">
    @yield('content')
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
        }
    </script>
    @stack('scripts')
</body>
</html>
