<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Anugerah3D admin dashboard.">

        <title>@yield('title', 'Anugerah3D Admin')</title>

        @fonts
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-[#f8fafd] text-slate-950 antialiased selection:bg-blue-200 selection:text-blue-950">
        <div class="min-h-screen lg:grid lg:grid-cols-[288px_minmax(0,1fr)]">
            <x-admin.sidebar />

            <div class="flex min-h-screen min-w-0 flex-col">
                <x-admin.header />

                <main class="flex-1 px-5 py-6 sm:px-8 lg:px-10">
                    @yield('content')
                </main>

                <x-admin.footer />
            </div>
        </div>
    </body>
</html>
