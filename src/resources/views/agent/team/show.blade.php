@extends('agent.layouts.app')
@section('title', $teamAgent->agt_name . ' | My Team')
@section('page_title', 'Agent Details')
@section('back_url', route('agent.team.index'))

@php
    $profileUrl = $teamAgent->profile_picture
        ? (filter_var($teamAgent->profile_picture, FILTER_VALIDATE_URL) ? $teamAgent->profile_picture : asset($teamAgent->profile_picture))
        : null;
@endphp

@section('content')
<div class="space-y-5">
    <section class="overflow-hidden rounded-[1.75rem] bg-[linear-gradient(145deg,#17324d,#285875)] p-5 text-white shadow-xl shadow-slate-900/10">
        <div class="flex items-center gap-4">
            <span class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-2xl bg-white/15 text-lg font-black text-white">
                @if ($profileUrl)
                    <img src="{{ $profileUrl }}" alt="{{ $teamAgent->agt_name }}" class="h-full w-full object-cover">
                @else
                    {{ $teamAgent->initials() }}
                @endif
            </span>
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-orange-300">Team member</p>
                <h2 class="truncate text-2xl font-black tracking-tight">{{ $teamAgent->agt_name }}</h2>
                <p class="truncate text-sm text-slate-300">{{ $teamAgent->login_id }} • {{ ucfirst($teamAgent->agt_status) }}</p>
            </div>
        </div>
        <div class="mt-5 grid grid-cols-2 gap-3">
            <div class="rounded-2xl bg-white/10 px-3 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-300">Total sales</p>
                <p class="mt-1 text-2xl font-black">RM {{ number_format($completedOrderTotal, 2) }}</p>
            </div>
            <div class="rounded-2xl bg-white/10 px-3 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-300">Completed orders</p>
                <p class="mt-1 text-2xl font-black">{{ number_format($completedOrderCount) }}</p>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">Pending orders</p>
            <p class="mt-1 text-xl font-black text-[#17324d]">{{ number_format($pendingOrderCount) }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">Discount</p>
            <p class="mt-1 text-xl font-black text-[#17324d]">{{ number_format((float) $teamAgent->discount_percentage, 1) }}%</p>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-extrabold uppercase tracking-[0.14em] text-slate-500">Agent information</h3>
        <div class="mt-4 space-y-3">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Full name</span>
                <span class="text-sm font-bold text-[#17324d] text-right">{{ $teamAgent->agt_name }}</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Login ID</span>
                <span class="text-sm font-bold text-[#17324d] text-right">{{ $teamAgent->login_id }}</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Email</span>
                <span class="text-sm font-bold text-[#17324d] text-right">{{ $teamAgent->email ?: '-' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Phone</span>
                <span class="text-sm font-bold text-[#17324d] text-right">{{ $teamAgent->phone_number ?: '-' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Referred by</span>
                <span class="text-sm font-bold text-[#17324d] text-right">{{ $teamAgent->referrer?->agt_name ?: $agent->agt_name }}</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Referral code</span>
                <span class="text-sm font-bold text-[#17324d] text-right">{{ $teamAgent->referral_code ?: '-' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Joined date</span>
                <span class="text-sm font-bold text-[#17324d] text-right">{{ $teamAgent->created_at?->format('d M Y, h:i A') ?: '-' }}</span>
            </div>
        </div>
    </section>
</div>
@endsection