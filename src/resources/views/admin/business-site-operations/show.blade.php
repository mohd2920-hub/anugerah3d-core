@extends('admin.layouts.app')

@section('title', $operation->businessSite->site_name.' Operation | Anugerah3D Admin')
@section('page_title', 'Business Site Details')

@section('content')
<div class="space-y-6">
    @error('business_site_operation')
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div>
        <a href="{{ route('admin.business-sites.index') }}" class="text-sm font-semibold text-[#1a73e8]">← Back to business sites</a>
        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-slate-950">{{ $operation->businessSite->site_name }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $operation->businessSite->city }}</p>
            </div>
            @if ($operation->closed_at)
                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">Closed</span>
            @else
                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">Open now</span>
            @endif
        </div>
        <div class="mt-4 flex flex-wrap gap-x-8 gap-y-2 text-sm text-slate-600">
            <p><span class="font-semibold text-slate-800">Open:</span> {{ $operation->opened_at->format('d M Y, h:i A') }}</p>
            <p><span class="font-semibold text-slate-800">Close:</span> {{ $operation->closed_at?->format('d M Y, h:i A') ?? 'Still open' }}</p>
        </div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2">
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total sales</p>
            <p class="mt-3 text-3xl font-bold text-[#1a73e8]">RM {{ number_format($summary['sales_total'], 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ number_format($summary['sales_count']) }} receipt(s)</p>
        </article>
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Items sold</p>
            <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($summary['items_sold']) }}</p>
            <p class="mt-1 text-sm text-slate-500">Total product units sold</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-semibold text-slate-950">Agent attendance</h3>
            <p class="mt-1 text-sm text-slate-500">Agents who checked in during this business session.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Agent</th><th class="px-5 py-3">Check in</th><th class="px-5 py-3">Check out</th><th class="px-5 py-3 text-right">Total time</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($attendances as $attendance)
                        @php
                            $attendanceEnd = $attendance->signed_out_at;
                            if ($operation->closed_at && (! $attendanceEnd || $attendanceEnd->greaterThan($operation->closed_at))) {
                                $attendanceEnd = $operation->closed_at;
                            }
                        @endphp
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $attendance->agent->agt_name }}</p><p class="mt-0.5 font-mono text-xs text-slate-500">{{ $attendance->agent->login_id }}</p></td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $attendance->signed_in_at->format('d M Y, h:i A') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $attendanceEnd?->format('d M Y, h:i A') ?? 'Still checked in' }}</td>
                            <td class="px-5 py-4 text-right"><span class="font-mono font-semibold text-slate-800" data-attendance-timer data-signed-in-at="{{ $attendance->signed_in_at->toIso8601String() }}" data-signed-out-at="{{ $attendanceEnd?->toIso8601String() }}">00:00:00</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No agents checked in during this session.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($attendances->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $attendances->links() }}</div>@endif
    </section>

    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-semibold text-slate-950">Sales and receipts</h3>
            <p class="mt-1 text-sm text-slate-500">All sales recorded during this business session.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Receipt</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Sales agent</th><th class="px-5 py-3 text-right">Items</th><th class="px-5 py-3 text-right">Total</th><th class="px-5 py-3 text-right">Action</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="px-5 py-4 font-mono font-semibold text-slate-900">{{ $sale->sale_number }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $sale->sold_at->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-4"><p class="font-semibold text-slate-800">{{ $sale->salesAgent->agt_name }}</p><p class="mt-0.5 text-xs text-slate-500">Recorded by {{ $sale->recordedBy->agt_name }}</p></td>
                            <td class="px-5 py-4 text-right text-slate-700">{{ number_format((int) $sale->items_sold) }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-slate-900">RM {{ number_format((float) $sale->total_amount, 2) }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.sales.show', $sale) }}" class="inline-flex rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-[#1a73e8] hover:bg-blue-50">Details</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No sales recorded during this session.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sales->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $sales->links() }}</div>@endif
    </section>
    <div class="flex justify-end border-t border-slate-200 pt-6">
        @if (! $operation->closed_at)
            <div class="text-right">
                <button type="button" disabled class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400">Delete session</button>
                <p class="mt-2 text-xs text-slate-500">Close this business session before deleting it.</p>
            </div>
        @elseif ($summary['sales_count'] > 0)
            <div class="text-right">
                <button type="button" disabled class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400">Delete session</button>
                <p class="mt-2 text-xs text-slate-500">This session cannot be deleted because it has sales.</p>
            </div>
        @else
            <form method="POST" action="{{ route('admin.business-site-operations.destroy', $operation) }}" onsubmit="return confirm('Delete this business session?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">Delete session</button>
            </form>
        @endif
    </div>

</div>
@endsection
