@extends('admin.layouts.app')

@section('title', $sale->sale_number.' | Sales')
@section('page_title', 'Sale Details')

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><a href="{{ route('admin.sales.index') }}" class="text-sm font-semibold text-[#1a73e8]">← Back to sales</a><h2 class="mt-2 font-mono text-xl font-semibold text-slate-950">{{ $sale->sale_number }}</h2><p class="mt-1 text-sm text-slate-500">{{ $sale->sold_at->format('d M Y, h:i A') }}</p></div>
        <div class="text-right"><p class="text-xs font-semibold uppercase text-slate-500">Sale total</p><p class="mt-1 text-2xl font-semibold text-[#1a73e8]">RM {{ number_format((float) $sale->total_amount, 2) }}</p></div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-5">
            <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70">
                <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Products sold</h3></div>
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-600"><tr><th class="px-5 py-3 text-left font-semibold">Product</th><th class="px-5 py-3 text-right font-semibold">Quantity</th><th class="px-5 py-3 text-right font-semibold">Unit price</th><th class="px-5 py-3 text-right font-semibold">Customer discount</th><th class="px-5 py-3 text-right font-semibold">Line total</th><th class="px-5 py-3 text-right font-semibold">Salesperson commission</th></tr></thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($sale->items as $item)
                                <tr><td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $item->product_name }}</p><p class="mt-1 font-mono text-xs text-slate-500">{{ $item->product_code }}</p></td><td class="px-5 py-4 text-right">{{ $item->quantity }}</td><td class="px-5 py-4 text-right">RM {{ number_format((float) $item->unit_price, 2) }}</td><td class="px-5 py-4 text-right"><p class="text-emerald-700">- RM {{ number_format((float) $item->customer_discount_amount, 2) }}</p><p class="mt-1 text-xs text-slate-500">Agent baseline {{ number_format((float) $item->agent_discount_percentage, 2) }}% · - RM {{ number_format((float) $item->agent_discount_amount, 2) }}</p></td><td class="px-5 py-4 text-right font-semibold">RM {{ number_format((float) $item->line_total, 2) }}</td><td class="px-5 py-4 text-right font-semibold text-[#1a73e8]">RM {{ number_format(((float) $item->unit_price * $item->quantity) - (float) $item->agent_discount_amount - (float) $item->customer_discount_amount, 2) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="divide-y divide-slate-100 md:hidden">
                    @foreach ($sale->items as $item)
                        <div class="p-4">
                            <div class="flex justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $item->product_name }}</p>
                                    <p class="mt-1 font-mono text-xs text-slate-500">{{ $item->product_code }}</p>
                                </div>
                                <p class="font-semibold text-slate-950">RM {{ number_format((float) $item->line_total, 2) }}</p>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">{{ $item->quantity }} × RM {{ number_format((float) $item->unit_price, 2) }}</p>
                            <p class="mt-1 text-xs font-medium text-emerald-700">Customer discount · - RM {{ number_format((float) $item->customer_discount_amount, 2) }}</p>
                            <p class="mt-1 text-xs text-slate-500">Agent baseline {{ number_format((float) $item->agent_discount_percentage, 2) }}% · - RM {{ number_format((float) $item->agent_discount_amount, 2) }}</p>
                            <p class="mt-1 text-xs font-semibold text-[#1a73e8]">Salesperson commission · RM {{ number_format(((float) $item->unit_price * $item->quantity) - (float) $item->agent_discount_amount - (float) $item->customer_discount_amount, 2) }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-slate-200 bg-slate-50 px-5 py-4">
                    <dl class="ml-auto max-w-sm space-y-2 text-sm"><div class="flex justify-between gap-4"><dt class="text-slate-500">Gross total</dt><dd class="font-medium text-slate-800">RM {{ number_format($itemSummary["gross_total"], 2) }}</dd></div><div class="flex justify-between gap-4"><dt class="text-slate-500">Total customer discount</dt><dd class="font-medium text-emerald-700">- RM {{ number_format($itemSummary["discount_total"], 2) }}</dd></div><div class="flex justify-between gap-4"><dt class="text-slate-500">Salesperson commission</dt><dd class="font-medium text-[#1a73e8]">RM {{ number_format($itemSummary["salesperson_commission_total"], 2) }}</dd></div><div class="flex justify-between gap-4 border-t border-slate-200 pt-2"><dt class="font-semibold text-slate-900">Sale total</dt><dd class="font-semibold text-slate-950">RM {{ number_format((float) $sale->total_amount, 2) }}</dd></div></dl>
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
                <p class="mt-4 font-semibold text-slate-900">{{ $sale->businessSite->site_name }}</p><p class="mt-1 text-sm text-slate-500">{{ $sale->businessSite->city }}</p><a href="{{ route('admin.business-site-operations.show', $sale->businessSiteOperation) }}" class="mt-4 inline-flex rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-[#1a73e8] hover:bg-blue-50">View session #{{ $sale->business_site_operation_id }}</a>
            </section>
            <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <h3 class="font-semibold text-slate-950">Agents</h3>
                <dl class="mt-4 space-y-4 text-sm"><div><dt class="text-xs font-semibold uppercase text-slate-500">Sales person</dt><dd class="mt-1 font-semibold text-slate-900">{{ $sale->salesAgent->agt_name }}</dd><dd class="text-xs text-slate-500">{{ $sale->salesAgent->login_id }}</dd></div><div><dt class="text-xs font-semibold uppercase text-slate-500">Recorded by</dt><dd class="mt-1 font-semibold text-slate-900">{{ $sale->recordedBy->agt_name }}</dd><dd class="text-xs text-slate-500">{{ $sale->recordedBy->login_id }}</dd></div></dl>
            </section>
            <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <h3 class="font-semibold text-slate-950">Salesperson commission</h3>
                <p class="mt-4 text-xs font-semibold uppercase text-slate-500">Formula</p>
                <p class="mt-1 text-sm text-slate-700">Selling total - agent discount baseline - customer discount</p>
                <p class="mt-4 text-2xl font-semibold text-[#1a73e8]">RM {{ number_format($itemSummary["salesperson_commission_total"], 2) }}</p>
            </section>
            <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <h3 class="font-semibold text-slate-950">POS session</h3>
                <dl class="mt-4 space-y-3 text-sm"><div><dt class="text-xs font-semibold uppercase text-slate-500">Checked in</dt><dd class="mt-1 text-slate-800">{{ $sale->posSession->signed_in_at->format('d M Y, h:i A') }}</dd></div><div><dt class="text-xs font-semibold uppercase text-slate-500">Checked out</dt><dd class="mt-1 text-slate-800">{{ $sale->posSession->signed_out_at?->format('d M Y, h:i A') ?? 'Active' }}</dd></div><div><dt class="text-xs font-semibold uppercase text-slate-500">Last updated</dt><dd class="mt-1 text-slate-800">{{ $sale->updated_at->format('d M Y, h:i A') }}</dd></div></dl>
            </section>
        </aside>
    </div>
</div>
@endsection
