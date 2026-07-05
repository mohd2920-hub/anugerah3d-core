<aside {{ $attributes->merge(['class' => 'border-b border-[#273154] bg-[#111827] text-white lg:border-b-0 lg:border-r']) }}>
    <div class="flex h-full flex-col px-5 py-5">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3" aria-label="Anugerah3D admin dashboard">
            <span class="grid h-11 w-11 place-items-center rounded-lg bg-white text-sm font-bold text-[#111827] shadow-sm">A3D</span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold">Anugerah3D</span>
                <span class="block truncate text-xs text-blue-200/80">Admin Operations</span>
            </span>
        </a>

        <div class="mt-6 h-1 rounded-full bg-[linear-gradient(90deg,#4285f4_0%,#a142f4_34%,#fbbc04_67%,#34a853_100%)]"></div>

        <nav class="mt-7 grid gap-1 text-sm font-medium" aria-label="Admin navigation">
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg bg-white px-3 py-2.5 text-[#1a73e8] shadow-sm">Dashboard</a>
            <a href="#" class="rounded-lg px-3 py-2.5 text-slate-300 transition hover:bg-white/10 hover:text-white">Orders</a>
            <a href="#" class="rounded-lg px-3 py-2.5 text-slate-300 transition hover:bg-white/10 hover:text-white">Products</a>
            <a href="#" class="rounded-lg px-3 py-2.5 text-slate-300 transition hover:bg-white/10 hover:text-white">Customers</a>
            <a href="#" class="rounded-lg px-3 py-2.5 text-slate-300 transition hover:bg-white/10 hover:text-white">Agents</a>
            <a href="#" class="rounded-lg px-3 py-2.5 text-slate-300 transition hover:bg-white/10 hover:text-white">Reports</a>
            <a href="#" class="rounded-lg px-3 py-2.5 text-slate-300 transition hover:bg-white/10 hover:text-white">Settings</a>
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
