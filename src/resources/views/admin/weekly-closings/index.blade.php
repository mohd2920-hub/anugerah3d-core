@extends('admin.layouts.app')

@section('title', 'Weekly Closing | Anugerah3D Admin')
@section('page_title', 'Weekly Closing')

@section('content')
<div class="space-y-5">
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-700">{{ session('success') }}</div>
    @endif

    <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <form method="GET" action="{{ route('admin.weekly-closings.index') }}" class="flex flex-col gap-3 md:flex-row md:items-center">
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
