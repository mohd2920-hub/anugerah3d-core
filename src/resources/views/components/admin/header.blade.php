@php
    $pageTitle = trim($__env->yieldContent('page_title', ''));

    if ($pageTitle === '') {
        $pageTitle = match (true) {
            request()->routeIs('admin.products.create') => 'Add Product',
            request()->routeIs('admin.products.edit') => 'Edit Product',
            request()->routeIs('admin.products.*') => 'Products',
            request()->routeIs('admin.profile.*') => 'Profile',
            request()->routeIs('admin.system.manage-data') => 'Manage Data',
            request()->routeIs('admin.system.activity-log') => 'Activity Log',
            request()->routeIs('admin.system.*') => 'Sys. Management',
            default => 'Dashboard',
        };
    }

    $adminUser = request()->user('admin');
    $nameParts = preg_split('/\s+/', trim((string) ($adminUser?->name ?? 'Admin'))) ?: [];
    $initials = collect($nameParts)
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
        ->implode('') ?: 'AD';
@endphp

<header {{ $attributes->merge(['class' => 'sticky top-0 z-20 border-b border-[#273154] bg-[#111827]/95 px-5 py-4 shadow-sm shadow-black/20 backdrop-blur sm:px-8 lg:px-10']) }}>
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">Admin Console</p>
            <h1 class="mt-1 truncate text-xl font-semibold text-white">{{ $pageTitle }}</h1>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="relative hidden min-w-72 md:block">
                <span class="sr-only">Search admin records</span>
                <input type="search" placeholder="Search orders, customers, products" class="h-10 w-full rounded-lg border border-white/10 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#4285f4] focus:ring-2 focus:ring-blue-200">
            </label>

            @if (request()->routeIs('admin.products.index'))
                <a href="{{ route('admin.products.create') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4285f4] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-950/30 transition hover:bg-[#1a73e8] focus:outline-none focus:ring-2 focus:ring-[#4285f4] focus:ring-offset-2 focus:ring-offset-[#111827]">
                    New Product
                </a>
            @elseif (request()->routeIs('admin.products.create', 'admin.products.edit'))
                <a href="{{ route('admin.products.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4285f4] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-950/30 transition hover:bg-[#1a73e8] focus:outline-none focus:ring-2 focus:ring-[#4285f4] focus:ring-offset-2 focus:ring-offset-[#111827]">
                    Products
                </a>
            @elseif (request()->routeIs('admin.dashboard'))
                <span class="inline-flex min-h-10 cursor-not-allowed select-none items-center justify-center rounded-lg border border-white/10 bg-white/10 px-4 text-sm font-semibold text-white/50">
                    New Order
                </span>
            @endif

            <a href="{{ route('admin.profile.show') }}" @class([
                'grid h-10 w-10 shrink-0 place-items-center rounded-lg text-sm font-bold transition focus:outline-none focus:ring-2 focus:ring-blue-200 focus:ring-offset-2 focus:ring-offset-[#111827]',
                'bg-blue-50 text-[#1a73e8] ring-2 ring-blue-300' => request()->routeIs('admin.profile.*'),
                'bg-white text-[#1a73e8] hover:bg-blue-50' => ! request()->routeIs('admin.profile.*'),
            ]) aria-label="View profile">
                {{ $initials }}
            </a>
        </div>
    </div>
</header>
