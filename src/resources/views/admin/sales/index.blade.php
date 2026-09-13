@extends('admin.layouts.app')

@section('title', $sales->total().' Sales | Anugerah3D Admin')
@section('page_title', $sales->total().' Sales')

@section('content')
@php
    $salesPageRoute = 'admin.sales.index';
@endphp
<div class="space-y-5" data-sales-page>
    @include('admin.sales._period-filters')

    <section aria-label="Sales summary">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Advanced sales summary</p>
                <p class="mt-1 text-sm font-medium text-slate-700">{{ $periodLabel }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3"><p class="text-xs text-slate-500">All values follow the active filters.</p>
                @adminRoute('admin.sales.transactions')
                <a href="{{ route('admin.sales.transactions', $filterQuery) }}" class="inline-flex min-h-11 items-center rounded-lg border border-emerald-700 bg-emerald-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Lihat Senarai Jualan</a>
                @endadminRoute
            </div>
        </div>
        <div class="sales-summary-strip">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Transactions</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format($summary['transaction_count']) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($summary['total_units']) }} units sold</p>
            </div>
            <div class="rounded-lg border border-cyan-200 bg-cyan-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Hari Jualan</p>
                <p class="mt-2 text-2xl font-semibold text-cyan-950">{{ number_format($summary['sales_days']) }} hari</p>
                <p class="mt-1 text-xs text-cyan-700">Tarikh berbeza dengan jualan tidak void</p>
            </div>
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Net sales</p>
                <p class="mt-2 text-2xl font-semibold text-blue-950">RM {{ number_format((float) $summary['total_amount'], 2) }}</p>
                <p class="mt-1 text-xs text-blue-700">Gross list value: RM {{ number_format((float) $summary['gross_amount'], 2) }}</p>
                <span class="mt-2 inline-flex rounded-md border border-blue-200 bg-white/70 px-2 py-1 text-xs font-semibold text-blue-900">{{ number_format($summary['total_units']) }} unit produk terjual</span>
            </div>
            @adminRoute('admin.sales.index')
<a href="{{ route('admin.sales.index', array_merge(request()->except(['page', 'discount_page', 'show_discounts']), $discountDetails ? [] : ['show_discounts' => 1])).($discountDetails ? '' : '#discount-breakdown') }}" class="rounded-lg border border-amber-200 bg-amber-50 p-4 shadow-sm transition hover:border-amber-400 hover:bg-amber-100">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Discounts</p>
                    <span class="text-xs font-semibold text-amber-800">{{ $discountDetails ? 'Hide breakdown' : 'View breakdown' }} ></span>
                </div>
                <p class="mt-2 text-2xl font-semibold text-amber-950">RM {{ number_format((float) $summary['discount_amount'], 2) }}</p>
                <p class="mt-2 text-xs text-amber-800">Customer discounts only</p>
                <span class="mt-2 inline-flex rounded-md border border-amber-200 bg-white/70 px-2 py-1 text-xs font-semibold text-amber-900">{{ number_format($summary['discounted_units']) }} unit produk diskaun</span>
            </a>
@endadminRoute
            <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Net company</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-950">RM {{ number_format((float) $summary['total_amount'], 2) }}</p>
                <p class="mt-1 text-xs text-indigo-700">No POS agent commission deduction</p>
            </div>
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Capital</p>
                <p class="mt-2 text-2xl font-semibold text-rose-950">RM {{ number_format((float) $summary['total_cost'], 2) }}</p>
                @if ($summary['missing_units'] > 0)
                    <p class="mt-1 text-xs text-rose-700">{{ $summary['missing_units'] }} unit memerlukan semakan kos / varian; keuntungan masih anggaran.</p>
                @endif
                <p class="mt-1 text-xs text-rose-700">Current product cost x units</p>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Gross profit</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-950">RM {{ number_format((float) $summary['profit_amount'], 2) }}</p>
                <p class="mt-1 text-xs text-emerald-700">Net company minus current product cost</p>

            </div>
        </div>
    </section>

    @if ($discountDetails)
        <section id="discount-breakdown" class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-amber-200" aria-label="Discount breakdown">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-amber-100 bg-amber-50 p-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Discount breakdown by transaction</p>
                    <p class="mt-1 text-sm text-amber-900">{{ $periodLabel }} - {{ number_format($discountDetails->total()) }} discounted product lines</p>
                </div>
                @adminRoute('admin.sales.index')
<a href="{{ route('admin.sales.index', request()->except(['page', 'discount_page', 'show_discounts'])) }}" class="inline-flex min-h-9 items-center rounded-lg border border-amber-300 bg-white px-3 text-sm font-semibold text-amber-800">Close</a>
@endadminRoute
            </div>
            <div class="border-b border-slate-200 p-5">
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs font-semibold uppercase text-slate-500">Customer discount</p><p class="mt-2 text-xl font-semibold text-slate-950">RM {{ number_format((float) $summary['customer_discount_amount'], 2) }}</p></div>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-data-table w-full min-w-[720px] text-xs">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Sale</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Agent / Customer</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Product</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-700">Customer discount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($discountDetails as $item)
                            <tr class="align-top hover:bg-slate-50">
                                <td class="px-4 py-4">
                                    @adminRoute('admin.sales.show')
<a href="{{ route('admin.sales.show', $item->posSale) }}" class="font-mono font-semibold text-[#1a73e8] hover:underline">{{ $item->posSale->sale_number }}</a>
@endadminRoute
                                    <p class="mt-1 text-slate-500">{{ $item->posSale->sold_at->format('d M Y, h:i A') }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ $item->posSale->salesAgent->agt_name }}</p>
                                    <p class="mt-1 text-slate-500">{{ $item->posSale->customer_name ?: 'Walk-in customer' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ $item->product_name }}</p>
                                    <p class="mt-1 font-mono text-slate-500">{{ $item->product_code }} - {{ number_format($item->quantity) }} units</p>
                                </td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-900">RM {{ number_format((float) $item->customer_discount_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-slate-500">No discounted sales match the selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($discountDetails->hasPages())
                <div class="border-t border-slate-200 p-4">{{ $discountDetails->links() }}</div>
            @endif
        </section>
    @endif

    <section class="grid gap-3 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,1.5fr)_minmax(0,1fr)]" aria-label="Sales performance">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sales by business site</p><p class="mt-1 text-xs text-slate-400">{{ $periodLabel }}</p></div>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $summary['by_site']->count() }} sites</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($summary['by_site'] as $siteSale)
                    <a href="{{ route('admin.sales.transactions', array_merge($filterQuery, ['business_site_id' => $siteSale->business_site_id])) }}" class="group flex cursor-pointer items-center justify-between gap-4 rounded-lg border border-transparent bg-slate-50 p-3 transition hover:border-blue-200 hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" title="LIHAT DETAIL · {{ $siteSale->businessSite->site_name }}">
                        <x-admin.rank-medal :rank="$loop->iteration" scope="site" class="rank-medal-compact" />
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-slate-900">{{ $siteSale->businessSite->site_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $siteSale->transaction_count }} transactions · {{ $siteSale->businessSite->city }}</p></div>
                        <div class="shrink-0 text-right"><p class="text-sm font-semibold text-slate-950">RM {{ number_format((float) $siteSale->total_amount, 2) }}</p><span class="text-xs font-semibold text-blue-700 opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100">LIHAT DETAIL</span></div>
                    </a>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No business site sales for this period.</p>
                @endforelse
            </div>
        </div>

        <section class="min-w-0 rounded-lg border border-amber-200 bg-amber-50 p-5 shadow-sm" aria-label="Top 10 products">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-amber-700">Top 10 Produk Paling Laris</h3>
            <p class="mt-1 text-xs text-amber-700">Mengikut unit terjual · {{ $periodLabel }}</p>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead><tr class="border-b border-amber-200 text-amber-800"><th class="py-2 pr-3">No.</th><th class="py-2 pr-3">Produk</th><th class="py-2 pr-3 text-right">Unit</th><th class="py-2 text-right">Jualan</th></tr></thead>
                    <tbody class="divide-y divide-amber-200/60">
                    @forelse ($summary['top_products'] as $product)
                        <tr><td class="py-3 pr-3 align-top"><x-admin.rank-medal :rank="$loop->iteration" scope="product" class="rank-medal-compact" /></td><td class="py-3 pr-3"><p class="font-semibold text-amber-950">{{ $product->product_name }}</p><p class="mt-1 break-all font-mono text-[10px] text-amber-700">{{ $product->product_code }}</p></td><td class="py-3 pr-3 text-right font-semibold tabular-nums text-amber-950">{{ number_format((int) $product->total_quantity) }}</td><td class="whitespace-nowrap py-3 text-right font-semibold tabular-nums text-amber-950">RM {{ number_format((float) $product->total_amount, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-amber-800">No product sales for this period.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="min-w-0 rounded-lg border border-emerald-200 bg-emerald-50 p-5 shadow-sm" aria-label="Top 3 agents">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Top 3 Ejen</h3>
            <p class="mt-1 text-xs text-emerald-700">Mengikut nilai jualan · {{ $periodLabel }}</p>
            <ol class="mt-4 space-y-3">
                @forelse ($summary['top_agents'] as $agentSale)
                    @php
                        $agent = $agentSale->salesAgent;
                        $profileUrl = $agent->profile_picture ? (filter_var($agent->profile_picture, FILTER_VALIDATE_URL) ? $agent->profile_picture : asset($agent->profile_picture)) : null;
                        $initials = collect(preg_split('/\s+/', trim($agent->agt_name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
                    @endphp
                    <li class="rounded-xl border border-emerald-100 bg-white/80 p-3">
                        <div class="flex items-center gap-3">
                            <x-admin.rank-medal :rank="$loop->iteration" />
                            @if ($profileUrl)
                                <img src="{{ $profileUrl }}" alt="{{ $agent->agt_name }}" class="h-11 w-11 shrink-0 rounded-full object-cover" loading="lazy">
                            @else
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-800" aria-label="{{ $agent->agt_name }}">{{ $initials }}</span>
                            @endif
                            <div class="min-w-0"><p class="break-words text-sm font-semibold text-emerald-950">{{ $agent->agt_name }}</p><p class="mt-1 break-all text-xs text-emerald-700">{{ $agent->login_id }}</p></div>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-emerald-100 pt-3 text-xs"><span class="text-emerald-700">{{ number_format((int) $agentSale->transaction_count) }} transaksi</span><strong class="text-emerald-950">RM {{ number_format((float) $agentSale->total_amount, 2) }}</strong></div>
                    </li>
                @empty
                    <li class="py-4 text-sm text-emerald-800">No agent sales for this period.</li>
                @endforelse
            </ol>
        </section>
    </section>

    @include('admin.sales._filter-modal')

</div>
@endsection
