<nav class="fixed inset-x-0 bottom-0 z-40 mx-auto max-w-xl border-t border-slate-200 bg-white/95 px-2 shadow-[0_-10px_30px_rgba(15,23,42,0.06)] backdrop-blur" style="padding-bottom: env(safe-area-inset-bottom);" aria-label="Agent app navigation">
    <div class="grid h-[72px] grid-cols-4">
        <a href="{{ route('agent.dashboard') }}" @class(['group flex flex-col items-center justify-center gap-1 text-[10px] font-bold transition', 'text-[#e7682b]' => request()->routeIs('agent.dashboard'), 'text-slate-400' => !request()->routeIs('agent.dashboard')])>
            <span @class(['grid h-8 w-12 place-items-center rounded-full transition', 'bg-orange-100' => request()->routeIs('agent.dashboard')])><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/></svg></span>
            Dashboard
        </a>
        <a href="{{ route('agent.history') }}" @class(['group flex flex-col items-center justify-center gap-1 text-[10px] font-bold transition', 'text-[#e7682b]' => request()->routeIs('agent.history'), 'text-slate-400' => !request()->routeIs('agent.history')])>
            <span @class(['grid h-8 w-12 place-items-center rounded-full transition', 'bg-orange-100' => request()->routeIs('agent.history')])><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6M12 7v5l3 2"/></svg></span>
            History
        </a>
        <a href="{{ route('agent.progress') }}" @class(['group flex flex-col items-center justify-center gap-1 text-[10px] font-bold transition', 'text-[#e7682b]' => request()->routeIs('agent.progress'), 'text-slate-400' => !request()->routeIs('agent.progress')])>
            <span @class(['grid h-8 w-12 place-items-center rounded-full transition', 'bg-orange-100' => request()->routeIs('agent.progress')])><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V9m6 10V5m6 14v-7m4 7H2"/></svg></span>
            My progress
        </a>
        <a href="{{ route('agent.profile') }}" @class(['group flex flex-col items-center justify-center gap-1 text-[10px] font-bold transition', 'text-[#e7682b]' => request()->routeIs('agent.profile'), 'text-slate-400' => !request()->routeIs('agent.profile')])>
            <span @class(['grid h-8 w-12 place-items-center rounded-full transition', 'bg-orange-100' => request()->routeIs('agent.profile')])><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
            Profile
        </a>
    </div>
</nav>
