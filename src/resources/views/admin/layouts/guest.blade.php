<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Anugerah3D admin system access.">

        <title>@yield('title', 'Anugerah3D Admin')</title>

        @fonts
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-[#111827] text-slate-950 antialiased selection:bg-blue-200 selection:text-blue-950">
        @yield('content')
    </body>
</html>
