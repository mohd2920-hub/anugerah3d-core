@extends('admin.layouts.app')
@section('title', 'Senarai Jualan | Anugerah3D Admin')
@section('page_title', 'Senarai Jualan · '.$sales->total().' Sales')
@section('content')
@php($salesPageRoute = 'admin.sales.transactions')
<div class="space-y-5" data-sales-page>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div><h2 class="text-xl font-semibold text-slate-900">Senarai Jualan</h2><p class="mt-1 text-sm text-slate-500">{{ $periodLabel }} · {{ number_format($sales->total()) }} rekod jualan</p></div>
        <a href="{{ route('admin.sales.index', $summaryReturnQuery) }}" class="inline-flex cursor-pointer items-center rounded-lg border border-blue-600 bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:border-blue-700 hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">← Kembali ke Ringkasan Sales</a>
    </div>
    @include('admin.sales._period-filters')
    @include('admin.sales._filter-modal')
    <section class="hidden overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70 md:block">
        <div class="overflow-x-auto">
            <table class="admin-data-table w-full min-w-[940px] text-xs">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Sale</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Business site</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Sales person</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Customer</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Payment</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">Products</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">Total</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($sales as $sale)
                        <tr class="group cursor-pointer transition hover:bg-blue-50 focus-within:bg-blue-50" data-sales-detail-row title="LIHAT DETAIL">
                            <td class="px-4 py-4">
                                @adminRoute('admin.sales.show')
<a href="{{ route('admin.sales.show', $sale) }}" data-sales-detail-link class="font-mono font-semibold text-[#1a73e8] hover:underline">{{ $sale->sale_number }} @if ($sale->voided_at)<span class="rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Void</span>@endif @if ($sale->correction_version > 0)<span class="rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Corrected</span>@endif</a>
@endadminRoute
                                <p class="mt-1 text-slate-500">{{ $sale->sold_at->format('d M Y, h:i A') }}</p>
                            </td>
                            <td class="px-4 py-4"><p class="font-semibold text-slate-900">{{ $sale->businessSite->site_name }}</p><p class="mt-1 text-slate-500">{{ $sale->businessSite->city }}</p></td>
                            <td class="px-4 py-4"><p class="font-semibold text-slate-900">{{ $sale->salesAgent->agt_name }}</p><p class="mt-1 text-slate-500">Logged by {{ $sale->recordedBy->agt_name }}</p></td>
                            <td class="px-4 py-4"><p class="font-medium text-slate-800">{{ $sale->customer_name ?: 'Walk-in customer' }}</p><p class="mt-1 text-slate-500">{{ $sale->customer_phone ?: 'No phone' }}</p></td>
                            <td class="px-4 py-4"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 font-semibold uppercase text-slate-700">{{ $sale->payment_method }}</span></td>
                            <td class="px-4 py-4 text-right"><p class="font-semibold text-slate-900">{{ number_format((int) $sale->total_units) }} units</p><p class="mt-1 text-slate-500">{{ $sale->items_count }} products</p></td>
                            <td class="px-4 py-4 text-right font-semibold text-slate-950">RM {{ number_format((float) $sale->total_amount, 2) }}</td>
                            <td class="px-4 py-4 text-right">@adminRoute('admin.sales.show')
<span class="whitespace-nowrap text-xs font-semibold text-blue-700 opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">LIHAT DETAIL</span>
@endadminRoute</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-12 text-center text-slate-500">No sales match the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="grid gap-3 md:hidden">
        @forelse ($sales as $sale)
            @adminRoute('admin.sales.show')
<a href="{{ route('admin.sales.show', $sale) }}" class="block rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition active:bg-slate-50">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="font-mono text-sm font-semibold text-[#1a73e8]">{{ $sale->sale_number }} @if ($sale->voided_at)<span class="rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Void</span>@endif @if ($sale->correction_version > 0)<span class="rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Corrected</span>@endif</p><p class="mt-1 text-xs text-slate-500">{{ $sale->sold_at->format('d M Y, h:i A') }}</p></div>
                    <p class="text-base font-semibold text-slate-950">RM {{ number_format((float) $sale->total_amount, 2) }}</p>
                </div>
                <div class="mt-4"><p class="font-semibold text-slate-900">{{ $sale->businessSite->site_name }}</p><p class="mt-1 text-sm text-slate-500">{{ $sale->businessSite->city }}</p></div>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs font-semibold uppercase text-slate-500">Sales person</dt><dd class="mt-1 font-semibold text-slate-900">{{ $sale->salesAgent->agt_name }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs font-semibold uppercase text-slate-500">Products</dt><dd class="mt-1 font-semibold text-slate-900">{{ number_format((int) $sale->total_units) }} units</dd></div>
                </dl>
                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold uppercase text-slate-700">{{ $sale->payment_method }}</span><span class="text-xs font-semibold text-[#1a73e8]">LIHAT DETAIL</span></div>
            </a>
@endadminRoute
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No sales match the selected filters.</div>
        @endforelse
    </section>

    {{ $sales->links() }}
</div>
@endsection
