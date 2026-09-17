@extends('admin.layouts.app')

@section('title', $sale->sale_number.' | Sales')
@section('page_title', 'Sale Details')

@section('content')
<div class="space-y-5">
    @if ($errors->any())<div role="alert" class="rounded-lg bg-red-50 p-4 text-red-700">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="flex flex-wrap items-center gap-3">
        @if ($sale->voided_at)<span class="rounded-full bg-red-100 px-3 py-1 text-sm font-bold text-red-700">Void</span>@else
@adminRoute('admin.sales.edit')
<a href="{{ route('admin.sales.edit', $sale) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Edit Sale</a>
@endadminRoute
@endif
        @if ($sale->correction_version > 0)<span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-bold text-amber-800">Corrected</span>@endif
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>@adminRoute('admin.sales.index')
<a href="{{ route('admin.sales.index') }}" data-sales-back class="text-sm font-semibold text-[#1a73e8]">← Back to sales</a>
@endadminRoute<h2 class="mt-2 font-mono text-xl font-semibold text-slate-950">{{ $sale->sale_number }}</h2><p class="mt-1 text-sm text-slate-500">{{ $sale->sold_at->format('d M Y, h:i A') }}</p></div>
        <div class="text-right"><p class="text-xs font-semibold uppercase text-slate-500">Net sales</p><p class="mt-1 text-2xl font-semibold text-[#1a73e8]">RM {{ number_format($itemSummary['net_sales_total'], 2) }}</p></div>
    </div>

    @if ($itemSummary['cost_incomplete'])
        <p class="mb-4 rounded-lg bg-amber-50 p-4 text-amber-800">Kos / maklumat varian belum lengkap. Jumlah modal hanya meliputi kos yang diketahui; keuntungan keseluruhan masih anggaran.</p>
    @endif
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-5">
            <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70">
                <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Products sold</h3></div>
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[1150px] text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-600"><tr><th class="px-5 py-3 text-left font-semibold">Product</th><th class="px-5 py-3 text-right font-semibold">Quantity</th><th class="px-5 py-3 text-right font-semibold">Unit price</th><th class="px-5 py-3 text-right font-semibold">Customer discount</th><th class="px-5 py-3 text-right font-semibold">Net sales</th><th class="px-5 py-3 text-right font-semibold">Net company</th><th class="px-5 py-3 text-right font-semibold">Capital</th><th class="px-5 py-3 text-right font-semibold">Gross profit</th></tr></thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($sale->items as $item)
                                @php
                                    $itemNetCompany = (float) $item->line_total;
                                    $itemCapital = (float) ($item->report_unit_cost ?? 0) * $item->quantity;
                                @endphp
                                <tr><td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $item->product_name }}</p><x-clicker-sale-details :configuration="$item->clicker_configuration ?? null" /><p class="mt-1 font-mono text-xs text-slate-500">{{ $item->product_code }}</p></td><td class="px-5 py-4 text-right">{{ $item->quantity }}</td><td class="px-5 py-4 text-right">RM {{ number_format((float) $item->unit_price, 2) }}</td><td class="px-5 py-4 text-right text-emerald-700">- RM {{ number_format((float) $item->customer_discount_amount, 2) }}</td><td class="px-5 py-4 text-right font-semibold">RM {{ number_format((float) $item->line_total, 2) }}</td><td class="px-5 py-4 text-right font-semibold text-blue-700">RM {{ number_format($itemNetCompany, 2) }}</td><td class="px-5 py-4 text-right font-semibold text-slate-800">{{ $item->report_unit_cost === null ? $item->report_cost_issue : 'RM '.number_format($itemCapital, 2) }}</td><td class="px-5 py-4 text-right font-semibold text-emerald-700">{{ $item->report_unit_cost === null ? 'Belum dapat dikira' : 'RM '.number_format($itemNetCompany - $itemCapital, 2) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="divide-y divide-slate-100 md:hidden">
                    @foreach ($sale->items as $item)
                        <div class="p-4">
                            <div class="flex justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $item->product_name }}</p><x-clicker-sale-details :configuration="$item->clicker_configuration ?? null" />
                                    <p class="mt-1 font-mono text-xs text-slate-500">{{ $item->product_code }}</p>
                                </div>
                                <p class="font-semibold text-slate-950">RM {{ number_format((float) $item->line_total, 2) }}</p>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">{{ $item->quantity }} × RM {{ number_format((float) $item->unit_price, 2) }}</p>
                            <p class="mt-1 text-xs font-medium text-emerald-700">Customer discount · - RM {{ number_format((float) $item->customer_discount_amount, 2) }}</p>
                            @php
                                $itemNetCompany = (float) $item->line_total;
                                $itemCapital = (float) ($item->report_unit_cost ?? 0) * $item->quantity;
                            @endphp
                            <p class="mt-1 text-xs font-semibold text-blue-700">Net company · RM {{ number_format($itemNetCompany, 2) }}</p>
                            <p class="mt-1 text-xs text-slate-600">Capital · {{ $item->report_unit_cost === null ? $item->report_cost_issue : 'RM '.number_format($itemCapital, 2) }}</p>
                            <p class="mt-1 text-xs font-semibold text-emerald-700">Gross profit · {{ $item->report_unit_cost === null ? 'Belum dapat dikira' : 'RM '.number_format($itemNetCompany - $itemCapital, 2) }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-slate-200 bg-slate-50 px-5 py-4">
                    <dl class="ml-auto max-w-sm space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Gross sales</dt><dd class="font-medium text-slate-800">RM {{ number_format($itemSummary['gross_total'], 2) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Customer discount</dt><dd class="font-medium text-emerald-700">- RM {{ number_format($itemSummary['discount_total'], 2) }}</dd></div>
                        <div class="flex justify-between gap-4 border-t border-slate-200 pt-2"><dt class="font-semibold text-slate-900">Net sales</dt><dd class="font-semibold text-slate-950">RM {{ number_format($itemSummary['net_sales_total'], 2) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Net company</dt><dd class="font-medium text-blue-700">RM {{ number_format($itemSummary['net_company_total'], 2) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Capital</dt><dd class="font-medium text-slate-800">RM {{ number_format($itemSummary['capital_total'], 2) }}</dd></div>
                        <div class="flex justify-between gap-4 border-t border-slate-200 pt-2"><dt class="font-semibold text-slate-900">Gross profit</dt><dd class="font-semibold text-emerald-700">RM {{ number_format($itemSummary['gross_profit_total'], 2) }}</dd></div>
                    </dl>
                </div>
            </section>
            <section class="grid gap-5 lg:grid-cols-2">
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <h3 class="font-semibold text-slate-950">Customer</h3>
                    <dl class="mt-4 space-y-3 text-sm"><div><dt class="text-xs font-semibold uppercase text-slate-500">Name</dt><dd class="mt-1 text-slate-800">{{ $sale->customer_name ?: 'Walk-in customer' }}</dd></div><div><dt class="text-xs font-semibold uppercase text-slate-500">Phone</dt><dd class="mt-1 text-slate-800">{{ $sale->customer_phone ?: 'Not provided' }}</dd></div><div><dt class="text-xs font-semibold uppercase text-slate-500">Remark</dt><dd class="mt-1 whitespace-pre-line leading-6 text-slate-700">{{ $sale->remark ?: 'No remark.' }}</dd></div></dl>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <h3 class="font-semibold text-slate-950">Payment</h3>
                    <dl class="mt-4 space-y-3 text-sm"><div><dt class="text-xs font-semibold uppercase text-slate-500">Method</dt><dd class="mt-1 font-semibold uppercase text-slate-800">{{ $paymentMethods[$sale->payment_method] ?? $sale->payment_method }}</dd></div><div><dt class="text-xs font-semibold uppercase text-slate-500">Remark</dt><dd class="mt-1 leading-6 text-slate-700">{{ $sale->payment_remark ?: 'No payment remark.' }}</dd></div></dl>
                </div>
            </section>

            @if (count($sale->salePicturePaths()) > 0 || count($sale->paymentProofPaths()) > 0)
                <section class="grid gap-5 sm:grid-cols-2">
                    @if (count($sale->salePicturePaths()) > 0)
                        <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70">
                            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">Sale picture</div>
                            <div class="grid grid-cols-2 gap-2 p-3">
                                @foreach ($sale->salePictureUrls() as $url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-slate-200">
                                        <img src="{{ $url }}" alt="Sale evidence for {{ $sale->sale_number }}" class="h-32 w-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if (count($sale->paymentProofPaths()) > 0)
                        <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70">
                            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">Payment proof</div>
                            <div class="grid grid-cols-2 gap-2 p-3">
                                @foreach ($sale->paymentProofUrls() as $url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-slate-200">
                                        <img src="{{ $url }}" alt="Payment proof for {{ $sale->sale_number }}" class="h-32 w-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            @endif
        </div>

        <aside class="space-y-5">
            <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <h3 class="font-semibold text-slate-950">Business site</h3>
                <p class="mt-4 font-semibold text-slate-900">{{ $sale->businessSite->site_name }}</p><p class="mt-1 text-sm text-slate-500">{{ $sale->businessSite->city }}</p>@adminRoute('admin.business-site-operations.show')
<a href="{{ route('admin.business-site-operations.show', $sale->businessSiteOperation) }}" class="mt-4 inline-flex rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-[#1a73e8] hover:bg-blue-50">View session #{{ $sale->business_site_operation_id }}</a>
@endadminRoute
            </section>
            <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <h3 class="font-semibold text-slate-950">Agents</h3>
                <dl class="mt-4 space-y-4 text-sm"><div><dt class="text-xs font-semibold uppercase text-slate-500">Sales person</dt><dd class="mt-1 font-semibold text-slate-900">{{ $sale->salesAgent->agt_name }}</dd><dd class="text-xs text-slate-500">{{ $sale->salesAgent->login_id }}</dd></div><div><dt class="text-xs font-semibold uppercase text-slate-500">Recorded by</dt><dd class="mt-1 font-semibold text-slate-900">{{ $sale->recordedBy?->agt_name ?? 'Admin (see correction history)' }}</dd><dd class="text-xs text-slate-500">{{ $sale->recordedBy?->login_id }}</dd></div></dl>
            </section>
            <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <h3 class="font-semibold text-slate-950">Financial summary</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Net sales</dt><dd class="font-semibold text-slate-900">RM {{ number_format($itemSummary['net_sales_total'], 2) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Net company</dt><dd class="font-semibold text-blue-700">RM {{ number_format($itemSummary['net_company_total'], 2) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Capital</dt><dd class="font-semibold text-slate-800">RM {{ number_format($itemSummary['capital_total'], 2) }}</dd></div>
                    <div class="flex justify-between gap-4 border-t border-slate-200 pt-3"><dt class="font-semibold text-slate-900">Gross profit</dt><dd class="font-semibold text-emerald-700">RM {{ number_format($itemSummary['gross_profit_total'], 2) }}</dd></div>
                </dl>
            </section>
            <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <h3 class="font-semibold text-slate-950">POS session</h3>
                <dl class="mt-4 space-y-3 text-sm"><div><dt class="text-xs font-semibold uppercase text-slate-500">Checked in</dt><dd class="mt-1 text-slate-800">{{ $sale->posSession?->signed_in_at->format('d M Y, h:i A') }}</dd></div><div><dt class="text-xs font-semibold uppercase text-slate-500">Checked out</dt><dd class="mt-1 text-slate-800">{{ $sale->posSession?->signed_out_at?->format('d M Y, h:i A') ?? ($sale->pos_session_id ? 'Active' : 'Not applicable') }}</dd></div><div><dt class="text-xs font-semibold uppercase text-slate-500">Last updated</dt><dd class="mt-1 text-slate-800">{{ $sale->updated_at->format('d M Y, h:i A') }}</dd></div></dl>
            </section>
        </aside>
    </div>
</div>
    @if (!$sale->voided_at && \App\Support\AdminAccess::allows(auth('admin')->user(), 'sales.void'))
    <details class="mt-5 rounded-xl bg-white p-5 ring-1 ring-red-200"><summary class="cursor-pointer font-semibold text-red-700">Void Sale</summary><form class="mt-4 space-y-3" method="POST" action="{{ route('admin.sales.preview', $sale) }}">@csrf<input type="hidden" name="action" value="void"><label class="block text-sm">Reason for voiding<textarea required minlength="5" maxlength="2000" name="reason" class="mt-1 w-full rounded-lg border-slate-300"></textarea></label><button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Preview Void Sale</button></form></details>
    @endif
    <section class="mt-5 space-y-4"><h2 class="text-lg font-semibold">Correction history</h2>@forelse ($sale->corrections as $correction)<details class="rounded-xl bg-white p-5 ring-1 ring-slate-200"><summary class="cursor-pointer text-sm font-semibold">{{ ucfirst($correction->action) }} · {{ $correction->admin?->name ?? 'Admin' }} · {{ $correction->created_at->format('d M Y H:i:s') }}</summary><p class="mt-3 text-sm">{{ $correction->reason }}</p><div class="mt-4 grid gap-5 md:grid-cols-2">@foreach (['before' => 'Before', 'after' => 'After'] as $key => $label)<div><h3 class="mb-3 font-bold">{{ $label }}</h3>@include('admin.sales._snapshot', ['snapshot' => $correction->$key])</div>@endforeach</div></details>@empty<p class="text-sm text-slate-500">No corrections recorded.</p>@endforelse</section>
@endsection
