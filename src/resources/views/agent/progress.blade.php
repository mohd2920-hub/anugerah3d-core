@extends('agent.layouts.app')
@section('title', 'My Progress | Anugerah3D Agent')
@section('page_title', 'My progress')

@section('content')
<div class="space-y-5">
    <section class="overflow-hidden rounded-[1.75rem] bg-[linear-gradient(145deg,#17324d,#285875)] p-5 text-white shadow-xl shadow-slate-900/10">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-orange-300">Sales performance</p>
                <p class="mt-3 text-3xl font-black tracking-tight">RM {{ number_format((float) $agent->total_sale, 2) }}</p>
                <p class="mt-1 text-sm text-slate-300">Recorded total sales</p>
            </div>
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/10 text-orange-300"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 17 6-6 4 4 8-9"/><path d="M14 6h7v7"/></svg></span>
        </div>
        <div class="mt-7">
            <div class="flex justify-between text-xs font-semibold"><span>Next RM {{ number_format($monthlyTarget, 0) }} milestone</span><span>{{ $progressPercentage }}%</span></div>
            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-white/15"><div class="h-full rounded-full bg-[#f28a52]" style="width: {{ $progressPercentage }}%"></div></div>
            <p class="mt-2 text-xs text-slate-300">RM {{ number_format($remainingTarget, 2) }} remaining to reach this milestone.</p>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-orange-100 text-[#e7682b]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
            <p class="mt-4 text-xs font-semibold text-slate-500">Agent discount</p>
            <p class="mt-1 text-xl font-black text-[#17324d]">{{ number_format((float) $agent->discount_percentage, 1) }}%</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-100 text-emerald-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg></span>
            <p class="mt-4 text-xs font-semibold text-slate-500">Account status</p>
            <p class="mt-1 text-xl font-black capitalize text-[#17324d]">{{ $agent->agt_status }}</p>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-2xl bg-cyan-100 text-cyan-700"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path d="M12 7v5l3 2"/></svg></span>
            <div><h2 class="font-extrabold text-[#17324d]">Progress updates</h2><p class="text-xs text-slate-500">Your latest account information</p></div>
        </div>
        <div class="mt-5 space-y-4 border-l-2 border-slate-100 pl-5">
            <div class="relative"><span class="absolute -left-[25px] top-1 h-2 w-2 rounded-full bg-[#e7682b] ring-4 ring-orange-50"></span><p class="text-sm font-bold text-slate-700">Last platform login</p><p class="mt-1 text-xs text-slate-500">{{ $agent->last_login_at?->format('d M Y, h:i A') ?: 'This is your first recorded login.' }}</p></div>
            <div class="relative"><span class="absolute -left-[25px] top-1 h-2 w-2 rounded-full bg-cyan-600 ring-4 ring-cyan-50"></span><p class="text-sm font-bold text-slate-700">Sales record is synced</p><p class="mt-1 text-xs text-slate-500">Values shown are managed by Anugerah3D operations.</p></div>
        </div>
    </section>
</div>
@endsection
