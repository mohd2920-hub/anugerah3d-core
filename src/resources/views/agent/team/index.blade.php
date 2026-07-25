@extends('agent.layouts.app')
@section('title', 'My Team | Anugerah3D Agent')
@section('page_title', 'My Team')
@section('back_url', route('agent.progress'))

@section('content')
<div class="space-y-5">
    <section class="overflow-hidden rounded-[1.75rem] border border-orange-200 bg-[linear-gradient(135deg,#fff7ed,#fffbeb)] p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#c6531e]">Referral network</p>
                <h2 class="mt-1 text-2xl font-black tracking-tight text-[#17324d]">Build and track your team</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Tier 1 are direct referrals. Tier 2 are referrals invited by your Tier 1 team.</p>
            </div>
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#e7682b] text-white shadow-md shadow-orange-600/20"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6m3-3h-6"/></svg></span>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-white/70 bg-white/90 px-3 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Total agents</p>
                <p class="mt-1 text-2xl font-black text-[#17324d]">{{ number_format($teamAgentCount) }}</p>
            </div>
            <div class="rounded-2xl border border-white/70 bg-white/90 px-3 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Team sales</p>
                <p class="mt-1 text-2xl font-black text-[#17324d]">RM {{ number_format($teamTotalSales, 2) }}</p>
                <p class="text-[11px] font-semibold text-slate-500">{{ number_format($teamOrderCount) }} completed orders</p>
            </div>
        </div>
    </section>

    <section class="space-y-4 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700">Bonus summary</p>
            <h3 class="mt-1 text-lg font-black text-[#17324d]">How your team bonus works</h3>
            <p class="mt-1 text-xs leading-5 text-slate-600">Your <span class="font-bold">discount</span> is for your own orders. Team bonus is different: Tier 1 sales pay {{ number_format($tier1Rate, 2) }}% and Tier 2 sales pay {{ number_format($tier2Rate, 2) }}%.</p>

            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-emerald-200 bg-white px-3 py-3">
                    <p class="text-xs font-semibold text-slate-500">Tier 1 bonus estimate</p>
                    <p class="mt-1 text-xl font-black text-[#17324d]">RM {{ number_format($tier1BonusEstimate, 2) }}</p>
                    <p class="text-[11px] font-semibold text-slate-500">{{ number_format($tier1Rate, 2) }}% × RM {{ number_format($tier1SalesTotal, 2) }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-white px-3 py-3">
                    <p class="text-xs font-semibold text-slate-500">Tier 2 bonus estimate</p>
                    <p class="mt-1 text-xl font-black text-[#17324d]">RM {{ number_format($tier2BonusEstimate, 2) }}</p>
                    <p class="text-[11px] font-semibold text-slate-500">{{ number_format($tier2Rate, 2) }}% × RM {{ number_format($tier2SalesTotal, 2) }}</p>
                </div>
            </div>

            <div class="mt-3 rounded-xl border border-emerald-300 bg-white px-3 py-3">
                <p class="text-xs font-semibold text-slate-500">Estimated total team bonus</p>
                <p class="mt-1 text-2xl font-black text-[#17324d]">RM {{ number_format($tier1BonusEstimate + $tier2BonusEstimate, 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 text-center">
            <div class="rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3">
                <p class="text-sm font-semibold text-slate-600">Tier 1</p>
                <p class="mt-1 text-xs font-bold text-slate-500">Direct referrals</p>
                <p class="mt-1 text-3xl font-black text-[#17324d]">{{ $tier1Agents->count() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3">
                <p class="text-sm font-semibold text-slate-600">Tier 2</p>
                <p class="mt-1 text-xs font-bold text-slate-500">From Tier 1 referrals</p>
                <p class="mt-1 text-3xl font-black text-[#17324d]">{{ $tier2ByReferrer->flatten(1)->count() }}</p>
            </div>
        </div>

        @if ($tier1Agents->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                <p class="text-sm font-bold text-slate-700">No team members yet.</p>
                <p class="mt-1 text-xs text-slate-500">Share your referral link from My Progress to start building your network.</p>
            </div>
        @else
            <div class="relative overflow-hidden rounded-3xl border-2 p-4 shadow-lg sm:p-5" style="border-color:#efb07b; background:linear-gradient(155deg,#fff2e3 0%,#ffe6cd 52%,#ffdcb8 100%); box-shadow:0 14px 28px rgba(214,130,63,0.18);">
                <span class="pointer-events-none absolute -top-12 -right-10 h-36 w-36 rounded-full bg-white/45 blur-2xl"></span>
                <span class="pointer-events-none absolute -bottom-14 -left-10 h-36 w-36 rounded-full bg-amber-100/45 blur-2xl"></span>

                <div class="relative flex items-center justify-between gap-3">
                    <h4 class="text-sm font-extrabold tracking-wide text-[#8a3f14]">Team Structure (Tier 1 -> Tier 2)</h4>
                    <span class="text-xs font-semibold text-[#a45a2d]">Tap any agent to view details</span>
                </div>

                <div class="relative mt-3 space-y-4">
                @foreach ($tier1Agents as $tier1Agent)
                    <div class="space-y-2">
                        <a href="{{ route('agent.team.show', $tier1Agent) }}" class="group flex items-center justify-between gap-3 rounded-2xl border px-3 py-3 transition active:scale-[0.995]" style="border-color:#ffd8bf; background:linear-gradient(160deg,#fffaf5 0%,#fff0e1 100%); box-shadow:0 8px 16px rgba(177,113,65,0.10), inset 0 1px 0 rgba(255,255,255,0.75);">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-xl text-xs font-extrabold text-[#17324d]" style="background:linear-gradient(155deg,#ffe9d1 0%,#ffd8b3 100%); box-shadow:inset 0 1px 0 rgba(255,255,255,0.7);">
                                    @if ($tier1Agent->profile_picture)
                                        <img src="{{ filter_var($tier1Agent->profile_picture, FILTER_VALIDATE_URL) ? $tier1Agent->profile_picture : asset($tier1Agent->profile_picture) }}" alt="{{ $tier1Agent->agt_name }}" class="h-full w-full object-cover">
                                    @else
                                        {{ $tier1Agent->initials() }}
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-xl font-bold tracking-tight text-slate-900">{{ $tier1Agent->agt_name }}</p>
                                    <p class="truncate text-xs font-semibold text-slate-500">{{ $tier1Agent->login_id }} • {{ ucfirst($tier1Agent->agt_status) }}</p>
                                </div>
                            </div>
                            <div class="shrink-0 rounded-lg border border-[#244567] px-3 py-2 text-right text-white shadow-sm" style="background:linear-gradient(155deg,#2f5379 0%,#1f3f5f 100%); box-shadow:0 8px 14px rgba(18,37,57,0.24);">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-200">Sales</p>
                                <p class="text-2xl font-black leading-none">RM{{ number_format((float) ($tier1Agent->completed_orders_total ?? 0), 2) }}</p>
                                <p class="text-[10px] font-semibold text-slate-200">{{ number_format((int) ($tier1Agent->completed_orders_count ?? 0)) }} orders</p>
                            </div>
                        </a>

                        @php($tier2Agents = $tier2ByReferrer->get($tier1Agent->id, collect()))

                        @if ($tier2Agents->isNotEmpty())
                            <div class="ml-3 space-y-2 border-l-2 pl-4" style="border-left-color:#f3c998;">
                                @foreach ($tier2Agents as $tier2Agent)
                                    <a href="{{ route('agent.team.show', $tier2Agent) }}" class="group flex items-center justify-between gap-3 rounded-2xl border px-3 py-3 transition active:scale-[0.995]" style="border-color:#ffd39c; background:linear-gradient(160deg,#fff5df 0%,#ffefcf 100%); box-shadow:0 8px 16px rgba(180,124,62,0.10), inset 0 1px 0 rgba(255,255,255,0.7);">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-xl text-xs font-extrabold text-[#17324d]" style="background:linear-gradient(155deg,#ffeab7 0%,#ffd990 100%); box-shadow:inset 0 1px 0 rgba(255,255,255,0.7);">
                                                @if ($tier2Agent->profile_picture)
                                                    <img src="{{ filter_var($tier2Agent->profile_picture, FILTER_VALIDATE_URL) ? $tier2Agent->profile_picture : asset($tier2Agent->profile_picture) }}" alt="{{ $tier2Agent->agt_name }}" class="h-full w-full object-cover">
                                                @else
                                                    {{ $tier2Agent->initials() }}
                                                @endif
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate text-xl font-bold tracking-tight text-slate-900">{{ $tier2Agent->agt_name }}</p>
                                                <p class="truncate text-xs font-semibold text-slate-500">{{ $tier2Agent->login_id }}</p>
                                            </div>
                                        </div>
                                        <div class="shrink-0 rounded-lg border border-[#9a5a24] px-3 py-2 text-right text-white shadow-sm" style="background:linear-gradient(155deg,#b46a2f 0%,#8f4d1f 100%); box-shadow:0 8px 14px rgba(102,54,18,0.22);">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-100">Sales</p>
                                            <p class="text-2xl font-black leading-none">RM{{ number_format((float) ($tier2Agent->completed_orders_total ?? 0), 2) }}</p>
                                            <p class="text-[10px] font-semibold text-amber-100">{{ number_format((int) ($tier2Agent->completed_orders_count ?? 0)) }} orders</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
                </div>
            </div>
        @endif
    </section>
</div>
@endsection