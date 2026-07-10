@php
    $systemActive = request()->routeIs('admin.system.*');
    $disabledNavClass = 'rounded-lg px-3 py-2.5 text-slate-500/70 cursor-not-allowed select-none';
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

<aside class="border-b border-[#273154] bg-[#111827] text-white lg:hidden">
    <details class="group px-5 py-4">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 [&::-webkit-details-marker]:hidden">
            <span class="flex min-w-0 items-center gap-3">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-white text-sm font-bold text-[#111827] shadow-sm">A3D</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold">Anugerah3D</span>
                    <span class="block truncate text-xs text-blue-200/80">Admin Operations</span>
                </span>
            </span>

            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-white/15 bg-white/10 transition group-open:bg-white group-open:text-[#111827]" aria-label="Open admin navigation">
                <span class="grid gap-1.5">
                    <span class="h-0.5 w-5 rounded-full bg-current"></span>
                    <span class="h-0.5 w-5 rounded-full bg-current"></span>
                    <span class="h-0.5 w-5 rounded-full bg-current"></span>
                </span>
            </span>
        </summary>

        <div class="mt-5 border-t border-white/10 pt-5">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">Admin Console</p>
            <h1 class="mt-1 text-xl font-semibold text-white">{{ $pageTitle }}</h1>

            <div class="mb-5">
                <button type="button" id="mobile-search-toggle" aria-expanded="false" aria-controls="mobile-search-form" class="mt-3 grid h-10 w-10 place-items-center rounded-lg border border-white/10 bg-white/[0.07] text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                </button>
                @php
                    $searchQuery = request('search', '');
                @endphp
                <form id="mobile-search-form" method="GET" action="{{ url()->current() }}" class="mt-2 hidden" style="display: none;">
                    <label class="sr-only" for="mobile-search-input">Search admin records</label>
                    <input id="mobile-search-input" name="search" type="search" value="{{ $searchQuery }}" placeholder="Search orders, customers, products" class="w-full rounded-lg border border-white/10 bg-white px-2 py-1.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-[#4285f4] focus:ring-2 focus:ring-blue-200">
                    @foreach (request()->except('search') as $key => $value)
                        @if (is_array($value))
                            @foreach ($value as $item)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                </form>
            </div>

            <div class="h-1 rounded-full bg-[linear-gradient(90deg,#4285f4_0%,#a142f4_34%,#fbbc04_67%,#34a853_100%)]"></div>

            <nav class="mt-5 grid gap-1 text-sm font-medium" aria-label="Admin navigation">
                <a href="{{ route('admin.dashboard') }}" @class([
                    'rounded-lg px-3 py-2.5 transition',
                    'bg-white text-[#1a73e8] shadow-sm' => request()->routeIs('admin.dashboard'),
                    'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.dashboard'),
                ])>Dashboard</a>
                <span class="{{ $disabledNavClass }}" aria-disabled="true">Orders</span>
                <a href="{{ route('admin.products.index') }}" @class([
                    'rounded-lg px-3 py-2.5 transition',
                    'bg-white text-[#1a73e8] shadow-sm' => request()->routeIs('admin.products.*'),
                    'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.products.*'),
                ])>Products</a>
                <span class="{{ $disabledNavClass }}" aria-disabled="true">Customers</span>
                <span class="{{ $disabledNavClass }}" aria-disabled="true">Agents</span>
                <span class="{{ $disabledNavClass }}" aria-disabled="true">Reports</span>

                <details class="group/sys" {{ $systemActive ? 'open' : '' }}>
                    <summary @class([
                        'flex cursor-pointer list-none items-center justify-between rounded-lg px-3 py-2.5 transition [&::-webkit-details-marker]:hidden',
                        'bg-white text-[#1a73e8] shadow-sm' => $systemActive,
                        'text-slate-300 hover:bg-white/10 hover:text-white' => ! $systemActive,
                    ])>
                        <span>Sys. Management</span>
                        <span class="text-xs transition group-open/sys:rotate-90">›</span>
                    </summary>
                    <div class="mt-1 grid gap-1 border-l border-white/10 pl-3">
                        <a href="{{ route('admin.system.manage-data') }}" @class([
                            'rounded-lg px-3 py-2 text-sm transition',
                            'bg-white/95 text-[#1a73e8]' => request()->routeIs('admin.system.manage-data'),
                            'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.system.manage-data'),
                        ])>Manage data</a>
                        <a href="{{ route('admin.system.activity-log') }}" @class([
                            'rounded-lg px-3 py-2 text-sm transition',
                            'bg-white/95 text-[#1a73e8]' => request()->routeIs('admin.system.activity-log'),
                            'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.system.activity-log'),
                        ])>Activity log</a>
                    </div>
                </details>

                <a href="{{ route('admin.profile.show') }}" @class([
                    'mt-3 inline-flex items-center gap-2 rounded-lg px-3 py-2.5 transition',
                    'bg-white text-[#1a73e8] shadow-sm' => request()->routeIs('admin.profile.*'),
                    'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.profile.*'),
                ])>
                    <span class="grid h-7 w-7 place-items-center rounded-md bg-white text-xs font-bold text-[#1a73e8]">{{ $initials }}</span>
                    <span>Profile</span>
                </a>
            </nav>

            <div class="mt-6 rounded-lg border border-white/10 bg-white/[0.07] p-4 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <p class="font-semibold text-white">Production</p>
                    <span class="rounded-lg bg-[#fbbc04] px-2 py-1 text-xs font-semibold text-[#111827]">Live</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-xs text-slate-300">
                    <div>
                        <p class="text-lg font-semibold text-white">7</p>
                        <p>In queue</p>
                    </div>
                    <div>
                        <p class="text-lg font-semibold text-white">2</p>
                        <p>Urgent</p>
                    </div>
                </div>
            </div>
        </div>
    </details>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var mobileToggle = document.getElementById('mobile-search-toggle');
        var mobileForm = document.getElementById('mobile-search-form');
        var mobileInput = document.getElementById('mobile-search-input');

        if (! mobileToggle || ! mobileForm || ! mobileInput) {
            return;
        }

        mobileToggle.addEventListener('click', function () {
            var expanded = mobileToggle.getAttribute('aria-expanded') === 'true';
            mobileToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            mobileForm.classList.toggle('hidden', expanded);
            mobileForm.style.display = expanded ? 'none' : 'block';

            if (! expanded) {
                mobileInput.focus();
            }
        });
    });
</script>

<aside {{ $attributes->merge(['class' => 'hidden border-b border-[#273154] bg-[#111827] text-white lg:flex lg:border-b-0 lg:border-r']) }}>
    <div class="flex h-full w-full flex-col px-5 py-5">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3" aria-label="Anugerah3D admin dashboard">
            <span class="grid h-11 w-11 place-items-center rounded-lg bg-white text-sm font-bold text-[#111827] shadow-sm">A3D</span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold">Anugerah3D</span>
                <span class="block truncate text-xs text-blue-200/80">Admin Operations</span>
            </span>
        </a>

        <div class="mt-6 h-1 rounded-full bg-[linear-gradient(90deg,#4285f4_0%,#a142f4_34%,#fbbc04_67%,#34a853_100%)]"></div>

        <nav class="mt-7 grid gap-1 text-sm font-medium" aria-label="Admin navigation">
            <a href="{{ route('admin.dashboard') }}" @class([
                'rounded-lg px-3 py-2.5 transition',
                'bg-white text-[#1a73e8] shadow-sm' => request()->routeIs('admin.dashboard'),
                'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.dashboard'),
            ])>Dashboard</a>
            <span class="{{ $disabledNavClass }}" aria-disabled="true">Orders</span>
            <a href="{{ route('admin.products.index') }}" @class([
                'rounded-lg px-3 py-2.5 transition',
                'bg-white text-[#1a73e8] shadow-sm' => request()->routeIs('admin.products.*'),
                'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.products.*'),
            ])>Products</a>
            <span class="{{ $disabledNavClass }}" aria-disabled="true">Customers</span>
            <span class="{{ $disabledNavClass }}" aria-disabled="true">Agents</span>
            <span class="{{ $disabledNavClass }}" aria-disabled="true">Reports</span>

            <details class="group/sys mt-1" {{ $systemActive ? 'open' : '' }}>
                <summary @class([
                    'flex cursor-pointer list-none items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold transition [&::-webkit-details-marker]:hidden',
                    'bg-white text-[#1a73e8] shadow-sm' => $systemActive,
                    'text-slate-300 hover:bg-white/10 hover:text-white' => ! $systemActive,
                ])>
                    <span>Sys. Management</span>
                    <span class="text-xs transition group-open/sys:rotate-90">›</span>
                </summary>
                <div class="mt-1 grid gap-1 border-l border-white/10 pl-3">
                    <a href="{{ route('admin.system.manage-data') }}" @class([
                        'rounded-lg px-3 py-2 text-sm transition',
                        'bg-white/95 text-[#1a73e8] shadow-sm' => request()->routeIs('admin.system.manage-data'),
                        'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.system.manage-data'),
                    ])>Manage data</a>
                    <a href="{{ route('admin.system.activity-log') }}" @class([
                        'rounded-lg px-3 py-2 text-sm transition',
                        'bg-white/95 text-[#1a73e8] shadow-sm' => request()->routeIs('admin.system.activity-log'),
                        'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.system.activity-log'),
                    ])>Activity log</a>
                </div>
            </details>
        </nav>

        <div class="mt-8 rounded-lg border border-white/10 bg-white/[0.07] p-4 text-sm lg:mt-auto">
            <div class="flex items-center justify-between gap-3">
                <p class="font-semibold text-white">Production</p>
                <span class="rounded-lg bg-[#fbbc04] px-2 py-1 text-xs font-semibold text-[#111827]">Live</span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 text-xs text-slate-300">
                <div>
                    <p class="text-lg font-semibold text-white">7</p>
                    <p>In queue</p>
                </div>
                <div>
                    <p class="text-lg font-semibold text-white">2</p>
                    <p>Urgent</p>
                </div>
            </div>
        </div>
    </div>
</aside>
