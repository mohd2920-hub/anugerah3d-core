<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="@yield('description', 'Anugerah3D creates custom 3D printed gifts, event items, corporate gifts and personal products.')">

        <title>@yield('title', 'Anugerah3D')</title>

        @fonts
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-[#f8faf8] text-zinc-900 antialiased">
        <x-public.header />

        <main id="top">
            @yield('content')
        </main>

        <x-public.footer />
    </body>
</html>
