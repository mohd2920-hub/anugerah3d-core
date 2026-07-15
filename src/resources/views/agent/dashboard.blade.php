@extends('agent.layouts.app')
@section('title', 'Dashboard | Anugerah3D Agent')
@section('page_title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <section class="relative overflow-hidden rounded-[1.75rem] bg-[#17324d] p-5 text-white shadow-xl shadow-slate-900/10">
        <div class="absolute -right-10 -top-12 h-40 w-40 rounded-full bg-[#e7682b]/30 blur-2xl"></div>
        <div class="relative">
            <p class="text-sm text-slate-300">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }},</p>
            <h2 class="mt-1 truncate text-2xl font-extrabold tracking-tight">{{ $agent->agt_name }}</h2>
            <div class="mt-6 grid grid-cols-2 gap-3">
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur"><p class="text-[11px] font-semibold uppercase tracking-wider text-slate-300">Total sales</p><p class="mt-2 text-xl font-black">RM {{ number_format((float) $agent->total_sale, 2) }}</p></div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur"><p class="text-[11px] font-semibold uppercase tracking-wider text-slate-300">Your discount</p><p class="mt-2 text-xl font-black">{{ number_format((float) $agent->discount_percentage, 1) }}%</p></div>
            </div>
        </div>
    </section>

    <a href="{{ route('agent.history') }}" class="flex items-center gap-4 rounded-3xl border border-amber-200 bg-[linear-gradient(135deg,#fffbeb,#fff7ed)] p-4 shadow-sm transition active:scale-[0.99]">
        <span class="grid h-12 w-12 flex-none place-items-center rounded-2xl bg-amber-100 text-amber-700"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l4 4v16H6z"/><path d="M14 2v5h5M9 13h6M9 17h4"/></svg></span>
        <div class="min-w-0 flex-1"><p class="text-[10px] font-bold uppercase tracking-[0.14em] text-amber-700">Pending order</p><p class="mt-1 text-xl font-black text-[#17324d]">{{ $pendingOrderItemCount }} <span class="text-sm font-bold text-slate-500">items</span></p></div>
        <svg class="h-5 w-5 flex-none text-amber-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
    </a>

    @include('agent.partials.product-ordering')
</div>
@endsection
