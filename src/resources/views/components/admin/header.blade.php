<header {{ $attributes->merge(['class' => 'sticky top-0 z-20 border-b border-[#273154] bg-[#111827]/95 px-5 py-4 shadow-sm shadow-black/20 backdrop-blur sm:px-8 lg:px-10']) }}>
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">Admin Console</p>
            <h1 class="mt-1 truncate text-xl font-semibold text-white">Dashboard</h1>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="relative hidden min-w-72 md:block">
                <span class="sr-only">Search admin records</span>
                <input type="search" placeholder="Search orders, customers, products" class="h-10 w-full rounded-lg border border-white/10 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#4285f4] focus:ring-2 focus:ring-blue-200">
            </label>

            <a href="#" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4285f4] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-950/30 transition hover:bg-[#1a73e8] focus:outline-none focus:ring-2 focus:ring-[#4285f4] focus:ring-offset-2 focus:ring-offset-[#111827]">
                New Order
            </a>

            <a href="{{ route('admin.login') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-white/20 bg-white/10 px-4 text-sm font-semibold text-white transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:ring-offset-2 focus:ring-offset-[#111827]">
                Sign Out
            </a>

            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white text-sm font-bold text-[#1a73e8]">AD</span>
        </div>
    </div>
</header>
