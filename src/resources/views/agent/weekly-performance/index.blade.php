@extends('agent.layouts.app')
@section('title', 'Weekly Performance | Anugerah3D Agent')
@section('page_title', 'Weekly performance')

@section('content')
<div class="space-y-4">
    <section class="rounded-3xl border border-cyan-200 bg-[linear-gradient(140deg,#ecfeff,#f0f9ff)] p-4 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-cyan-700">Weekly Closing History</p>
        <h2 class="mt-1 text-xl font-black text-[#17324d]">Refer your past performance</h2>
        <p class="mt-2 text-sm text-slate-600">All weekly snapshots are saved here including bonus, team orders and POS totals.</p>

        <form method="GET" action="{{ route('agent.weekly-performance.index') }}" class="mt-4 flex gap-2">
            <select name="status" class="h-11 min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-[#e7682b] focus:ring-4 focus:ring-orange-100">
                <option value="" @selected($status === '')>All status</option>
                <option value="pending" @selected($status === 'pending')>Pending</option>
                <option value="paid" @selected($status === 'paid')>Paid</option>
                <option value="no_payout" @selected($status === 'no_payout')>No payout</option>
            </select>
            <button type="submit" class="h-11 rounded-xl bg-[#17324d] px-4 text-sm font-extrabold text-white">Apply</button>
        </form>
    </section>

    @forelse ($rows as $row)
        <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $row->closing->week_key }}</p>
                    <p class="mt-1 text-sm font-bold text-[#17324d]">{{ $row->closing->period_start->format('d M Y') }} - {{ $row->closing->period_end->subSecond()->format('d M Y') }}</p>
                </div>
                <span @class([
                    'inline-flex rounded-full px-2.5 py-1 text-[0.65rem] font-bold',
                    'bg-amber-100 text-amber-800' => $row->payout_status === 'pending',
                    'bg-emerald-100 text-emerald-800' => $row->payout_status === 'paid',
                    'bg-slate-100 text-slate-700' => $row->payout_status === 'no_payout',
                ])>{{ Str::headline($row->payout_status) }}</span>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                <div class="rounded-2xl bg-cyan-50 p-3"><p class="font-semibold text-cyan-700">Tier 1 bonus</p><p class="mt-1 text-lg font-black text-[#17324d]">RM {{ number_format((float) $row->tier1_bonus, 2) }}</p></div>
                <div class="rounded-2xl bg-blue-50 p-3"><p class="font-semibold text-blue-700">Tier 2 bonus</p><p class="mt-1 text-lg font-black text-[#17324d]">RM {{ number_format((float) $row->tier2_bonus, 2) }}</p></div>
                <div class="rounded-2xl bg-orange-50 p-3"><p class="font-semibold text-orange-700">Total payable</p><p class="mt-1 text-lg font-black text-[#17324d]">RM {{ number_format((float) $row->total_bonus, 2) }}</p></div>
                <div class="rounded-2xl bg-emerald-50 p-3"><p class="font-semibold text-emerald-700">POS amount</p><p class="mt-1 text-lg font-black text-[#17324d]">RM {{ number_format((float) $row->pos_sales_amount, 2) }}</p></div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">
                <div class="rounded-xl border border-slate-200 p-2">Personal orders: <span class="font-bold text-slate-900">{{ number_format($row->personal_orders_count) }}</span></div>
                <div class="rounded-xl border border-slate-200 p-2">Personal amount: <span class="font-bold text-slate-900">RM {{ number_format((float) $row->personal_order_amount, 2) }}</span></div>
                <div class="rounded-xl border border-slate-200 p-2">Tier orders: <span class="font-bold text-slate-900">{{ number_format($row->tier1_orders_count + $row->tier2_orders_count) }}</span></div>
                <div class="rounded-xl border border-slate-200 p-2">New direct agents: <span class="font-bold text-slate-900">{{ number_format($row->new_agents_registered) }}</span></div>
            </div>
        </article>
    @empty
        <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-600 shadow-sm">No weekly performance data yet.</div>
    @endforelse

    <div class="pt-2">{{ $rows->links('pagination::tailwind') }}</div>
</div>
@endsection
