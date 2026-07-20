@php
    $pageTitle = trim($__env->yieldContent('page_title', ''));

    if ($pageTitle === '') {
        $pageTitle = match (true) {
            request()->routeIs('admin.products.create') => 'Add Product',
            request()->routeIs('admin.products.edit') => 'Edit Product',
            request()->routeIs('admin.agents.create') => 'Add Agent',
            request()->routeIs('admin.agents.edit') => 'Edit Agent',
            request()->routeIs('admin.orders.*') => 'Orders',
            request()->routeIs('admin.agents.*') => 'Agents',
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

<header {{ $attributes->merge(['class' => 'hidden sticky top-0 z-20 border-b border-[#273154] bg-[#111827]/95 px-5 py-4 shadow-sm shadow-black/20 backdrop-blur sm:px-8 lg:block lg:px-10']) }}>
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">Admin Console</p>
            <h1 class="mt-1 truncate text-xl font-semibold text-white">{{ $pageTitle }}</h1>
        </div>

        <div class="hidden flex-col gap-3 sm:flex sm:flex-row sm:items-center">
            @php
                $searchQuery = request('search', '');
            @endphp

            <div class="relative">
                <button type="button" id="admin-search-toggle" aria-expanded="false" aria-controls="admin-search-form" class="grid h-10 w-10 place-items-center rounded-lg bg-white text-[#1a73e8] shadow-sm shadow-blue-950/10 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <span class="sr-only">Open search</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                </button>

                <form id="admin-search-form" method="GET" action="{{ url()->current() }}" class="absolute right-0 top-0 z-10 flex items-center gap-2 rounded-lg border border-white/10 bg-white px-3 py-1.5 shadow-lg transition duration-200" style="transform: translateX(0); visibility: hidden; opacity: 0; pointer-events: none;">
                    <label class="sr-only" for="admin-search-input">Search admin records</label>
                    <input id="admin-search-input" name="search" type="search" value="{{ $searchQuery }}" placeholder="Search orders, customers, products" class="h-9 min-w-[280px] rounded-lg border border-white/10 bg-white px-2 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-[#4285f4] focus:ring-2 focus:ring-blue-200">
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

            @if (request()->routeIs('admin.products.create', 'admin.products.edit'))
                <a href="{{ route('admin.products.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4285f4] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-950/30 transition hover:bg-[#1a73e8] focus:outline-none focus:ring-2 focus:ring-[#4285f4] focus:ring-offset-2 focus:ring-offset-[#111827]">
                    Products
                </a>
            @endif

            @if (request()->routeIs('admin.agents.create', 'admin.agents.edit', 'admin.agents.show'))
                <a href="{{ route('admin.agents.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4285f4] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-950/30 transition hover:bg-[#1a73e8] focus:outline-none focus:ring-2 focus:ring-[#4285f4] focus:ring-offset-2 focus:ring-offset-[#111827]">
                    Agents
                </a>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('admin-search-toggle');
        var form = document.getElementById('admin-search-form');
        var input = document.getElementById('admin-search-input');

        if (! toggle || ! form || ! input) {
            return;
        }

        toggle.addEventListener('click', function () {
            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            
            if (expanded) {
                // Hide form
                form.style.visibility = 'hidden';
                form.style.opacity = '0';
                form.style.pointerEvents = 'none';
            } else {
                // Show form
                form.style.visibility = 'visible';
                form.style.opacity = '1';
                form.style.pointerEvents = 'auto';
                input.focus();
            }
        });

        document.addEventListener('click', function (event) {
            if (! form.contains(event.target) && ! toggle.contains(event.target)) {
                if (form.style.visibility === 'visible' || form.style.visibility === '') {
                    toggle.setAttribute('aria-expanded', 'false');
                    form.style.visibility = 'hidden';
                    form.style.opacity = '0';
                    form.style.pointerEvents = 'none';
                }
            }
        });
    });
</script>
