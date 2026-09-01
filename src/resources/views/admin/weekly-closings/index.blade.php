@extends('admin.layouts.app')

@section('title', 'Weekly Closing | Anugerah3D Admin')
@section('page_title', 'Weekly Closing')

@section('content')
<div class="space-y-5">
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-700">{{ session('success') }}</div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-label="Tier payout report">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tier 1 and Tier 2 payment summary</p>
                <h2 class="mt-1 text-lg font-semibold text-slate-950">{{ $payoutReport['period_label'] }}</h2>
                <p class="mt-1 text-xs text-slate-500">Read-only summary for closings completed in this period. Existing payment processes are unchanged.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (['week' => 'This week', 'month' => 'This month'] as $period => $label)
                    <a href="{{ route('admin.weekly-closings.index', array_merge(request()->except(['page', 'report_period', 'start_date', 'end_date']), ['report_period' => $period])) }}" @class([
                        'inline-flex min-h-9 items-center rounded-lg px-3 text-sm font-semibold',
                        'bg-[#1a73e8] text-white' => $payoutReport['filters']['report_period'] === $period,
                        'border border-slate-300 bg-white text-slate-700' => $payoutReport['filters']['report_period'] !== $period,
                    ])>{{ $label }}</a>
                @endforeach
            </div>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="GET" action="{{ route('admin.weekly-closings.index') }}" class="mt-4 grid gap-3 sm:grid-cols-[minmax(160px,1fr)_minmax(160px,1fr)_auto]">
            <input type="hidden" name="report_period" value="custom">
            @if ($search !== '')
                <input type="hidden" name="search" value="{{ $search }}">
            @endif
            <label>
                <span class="mb-1 block text-xs font-semibold text-slate-600">Start date</span>
                <input type="date" name="start_date" value="{{ $payoutReport['filters']['start_date'] }}" required class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
            </label>
            <label>
                <span class="mb-1 block text-xs font-semibold text-slate-600">End date</span>
                <input type="date" name="end_date" value="{{ $payoutReport['filters']['end_date'] }}" required class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
            </label>
            <button type="submit" class="mt-auto inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white">View report</button>
        </form>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Tier 1</p>
                <p class="mt-2 text-xl font-semibold text-blue-950">RM {{ number_format((float) $payoutReport['historical']['tier1_total'], 2) }}</p>
                <p class="mt-1 text-xs text-blue-700">Paid RM {{ number_format((float) $payoutReport['historical']['tier1_paid'], 2) }}</p>
                <p class="text-xs text-blue-700">Pending RM {{ number_format((float) $payoutReport['historical']['tier1_pending'], 2) }}</p>
            </div>
            <div class="rounded-lg border border-violet-200 bg-violet-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Tier 2</p>
                <p class="mt-2 text-xl font-semibold text-violet-950">RM {{ number_format((float) $payoutReport['historical']['tier2_total'], 2) }}</p>
                <p class="mt-1 text-xs text-violet-700">Paid RM {{ number_format((float) $payoutReport['historical']['tier2_paid'], 2) }}</p>
                <p class="text-xs text-violet-700">Pending RM {{ number_format((float) $payoutReport['historical']['tier2_pending'], 2) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Total payable</p>
                <p class="mt-2 text-xl font-semibold text-slate-950">RM {{ number_format((float) $payoutReport['historical']['payable_total'], 2) }}</p>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Paid</p>
                <p class="mt-2 text-xl font-semibold text-emerald-950">RM {{ number_format((float) $payoutReport['historical']['paid_total'], 2) }}</p>
                <p class="mt-1 text-xs text-emerald-700">{{ number_format($payoutReport['historical']['paid_records']) }} payout records</p>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Pending</p>
                <p class="mt-2 text-xl font-semibold text-amber-950">RM {{ number_format((float) $payoutReport['historical']['pending_total'], 2) }}</p>
                <p class="mt-1 text-xs text-amber-700">{{ number_format($payoutReport['historical']['pending_records']) }} payout records</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payable records</p>
                <p class="mt-2 text-xl font-semibold text-slate-950">{{ number_format($payoutReport['historical']['payout_records']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Across selected closings</p>
            </div>
        </div>
    </section>

    <section class="rounded-lg border border-teal-200 bg-teal-50 p-5 shadow-sm" aria-label="Current week estimated payout">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Current week - estimated upcoming payment</p>
                <h2 class="mt-1 text-lg font-semibold text-teal-950">{{ $payoutReport['current_week']['period_label'] }}</h2>
                <p class="mt-1 text-xs text-teal-700">Estimate based on current non-cancelled orders and existing Tier rates. Final amount is fixed only when weekly closing runs.</p>
            </div>
            <div class="rounded-lg bg-white px-4 py-3 text-right shadow-sm">
                <p class="text-xs font-semibold uppercase text-teal-700">Estimated total</p>
                <p class="mt-1 text-2xl font-semibold text-teal-950">RM {{ number_format((float) $payoutReport['current_week']['estimated_total'], 2) }}</p>
            </div>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg bg-white p-4"><p class="text-xs font-semibold uppercase text-slate-500">Tier 1 estimate</p><p class="mt-2 text-xl font-semibold text-slate-950">RM {{ number_format((float) $payoutReport['current_week']['tier1_bonus'], 2) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($payoutReport['current_week']['tier1_orders']) }} orders - RM {{ number_format((float) $payoutReport['current_week']['tier1_sales'], 2) }}</p></div>
            <div class="rounded-lg bg-white p-4"><p class="text-xs font-semibold uppercase text-slate-500">Tier 2 estimate</p><p class="mt-2 text-xl font-semibold text-slate-950">RM {{ number_format((float) $payoutReport['current_week']['tier2_bonus'], 2) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($payoutReport['current_week']['tier2_orders']) }} orders - RM {{ number_format((float) $payoutReport['current_week']['tier2_sales'], 2) }}</p></div>
            <div class="rounded-lg bg-white p-4"><p class="text-xs font-semibold uppercase text-slate-500">Estimated payees</p><p class="mt-2 text-xl font-semibold text-slate-950">{{ number_format($payoutReport['current_week']['estimated_payees']) }}</p><p class="mt-1 text-xs text-slate-500">Tier recipients with projected bonus</p></div>
            <div class="rounded-lg border border-teal-200 bg-white p-4"><p class="text-xs font-semibold uppercase text-teal-700">Payment window</p><p class="mt-2 font-semibold text-teal-950">{{ $payoutReport['current_week']['period_label'] }}</p><p class="mt-1 text-xs text-teal-700">Will enter the next weekly closing</p></div>
        </div>
    </section>

    <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <form method="GET" action="{{ route('admin.weekly-closings.index') }}" class="flex flex-col gap-3 md:flex-row md:items-center">
            <input type="hidden" name="report_period" value="{{ $payoutReport['filters']['report_period'] }}">
            @if ($payoutReport['filters']['report_period'] === 'custom')
                <input type="hidden" name="start_date" value="{{ $payoutReport['filters']['start_date'] }}">
                <input type="hidden" name="end_date" value="{{ $payoutReport['filters']['end_date'] }}">
            @endif
            <input name="search" type="search" value="{{ $search }}" placeholder="Search by week key (e.g. 2026-W30)" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100 md:max-w-sm">
            <div class="flex gap-2">
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white hover:bg-[#1558b0]">Search</button>
                @if ($search !== '')
                    <a href="{{ route('admin.weekly-closings.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <section class="hidden overflow-visible rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70 md:block">
        <div class="overflow-x-auto">
            <table class="admin-data-table w-full min-w-[980px] text-xs">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Week</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Period</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">Orders</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">POS</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">Payable bonus</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">Pending / Paid</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($closings as $closing)
                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="px-3 py-3">
                                <p class="font-semibold text-slate-900">{{ $closing->week_key }}</p>
                                <p class="mt-1 text-[11px] text-slate-500">Closed: {{ $closing->closed_at?->format('d M Y, h:i A') ?: '-' }}</p>
                            </td>
                            <td class="px-3 py-3 text-slate-600">
                                <p>{{ $closing->period_start->format('d M Y') }}</p>
                                <p class="mt-1 text-slate-400">{{ $closing->period_end->subSecond()->format('d M Y') }}</p>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <p class="font-semibold text-slate-900">{{ number_format($closing->total_orders) }}</p>
                                <p class="text-[11px] text-slate-500">RM {{ number_format((float) $closing->total_order_amount, 2) }}</p>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <p class="font-semibold text-slate-900">{{ number_format($closing->total_pos_sales) }}</p>
                                <p class="text-[11px] text-slate-500">RM {{ number_format((float) $closing->total_pos_amount, 2) }}</p>
                            </td>
                            <td class="px-3 py-3 text-right font-semibold text-slate-900">RM {{ number_format((float) $closing->total_payable_bonus, 2) }}</td>
                            <td class="px-3 py-3 text-right text-slate-600">{{ number_format($closing->pending_payout_count) }} / {{ number_format($closing->paid_payout_count) }}</td>
                            <td class="px-3 py-3 text-right">
                                <a href="{{ route('admin.weekly-closings.show', $closing) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 font-semibold text-slate-700 hover:bg-slate-50">Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-10 text-center text-slate-600">No weekly closing records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="grid gap-3 md:hidden">
        @forelse ($closings as $closing)
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm font-semibold text-[#1a73e8]">{{ $closing->week_key }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $closing->period_start->format('d M Y') }} - {{ $closing->period_end->subSecond()->format('d M Y') }}</p>
                    </div>
                    <a href="{{ route('admin.weekly-closings.show', $closing) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700">Details</a>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-lg bg-slate-50 p-2"><p class="text-slate-500">Orders</p><p class="font-semibold text-slate-900">{{ number_format($closing->total_orders) }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-2"><p class="text-slate-500">POS</p><p class="font-semibold text-slate-900">{{ number_format($closing->total_pos_sales) }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-2"><p class="text-slate-500">Payable</p><p class="font-semibold text-slate-900">RM {{ number_format((float) $closing->total_payable_bonus, 2) }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-2"><p class="text-slate-500">Pending / Paid</p><p class="font-semibold text-slate-900">{{ number_format($closing->pending_payout_count) }} / {{ number_format($closing->paid_payout_count) }}</p></div>
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-600 shadow-sm">No weekly closing records yet.</div>
        @endforelse
    </section>

    <div class="flex justify-center">{{ $closings->links('pagination::tailwind') }}</div>
</div>
@endsection
