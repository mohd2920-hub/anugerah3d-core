@extends('admin.layouts.app')

@section('title', $sales->total().' Sales | Anugerah3D Admin')
@section('page_title', $sales->total().' Sales')

@section('content')
<div class="space-y-5">
    <section class="overflow-x-auto rounded-lg bg-white p-2 shadow-sm ring-1 ring-slate-200/70" aria-label="Sales period">
        <div class="flex min-w-max gap-2">
            @foreach ($periodOptions as $value => $label)
                <a href="{{ route('admin.sales.index', array_merge(request()->except(['period', 'page', 'start_date', 'end_date']), ['period' => $value])) }}" @class([
                    'rounded-lg px-4 py-2.5 text-sm font-semibold transition',
                    'bg-[#1a73e8] text-white shadow-sm' => $filters['period'] === $value,
                    'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' => $filters['period'] !== $value,
                ])>{{ $label }}</a>
            @endforeach
        </div>
    </section>

    <section aria-label="Sales summary">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Advanced sales summary</p>
                <p class="mt-1 text-sm font-medium text-slate-700">{{ $periodLabel }}</p>
            </div>
            <p class="text-xs text-slate-500">All values follow the active filters below.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Transactions</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format($summary['transaction_count']) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($summary['total_units']) }} units sold</p>
            </div>
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Sales collected</p>
                <p class="mt-2 text-2xl font-semibold text-blue-950">RM {{ number_format((float) $summary['total_amount'], 2) }}</p>
                <p class="mt-1 text-xs text-blue-700">Gross list value: RM {{ number_format((float) $summary['gross_amount'], 2) }}</p>
            </div>
            <a href="{{ route('admin.sales.index', array_merge(request()->except(['page', 'discount_page', 'show_discounts']), $discountDetails ? [] : ['show_discounts' => 1])).($discountDetails ? '' : '#discount-breakdown') }}" class="rounded-lg border border-amber-200 bg-amber-50 p-4 shadow-sm transition hover:border-amber-400 hover:bg-amber-100">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Discounts</p>
                    <span class="text-xs font-semibold text-amber-800">{{ $discountDetails ? 'Hide breakdown' : 'View breakdown' }} ></span>
                </div>
                <p class="mt-2 text-2xl font-semibold text-amber-950">RM {{ number_format((float) $summary['discount_amount'], 2) }}</p>
                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-amber-800">
                    <span>Agent: RM {{ number_format((float) $summary['agent_discount_amount'], 2) }}</span>
                    <span>Customer: RM {{ number_format((float) $summary['customer_discount_amount'], 2) }}</span>
                </div>
            </a>
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Cost</p>
                <p class="mt-2 text-2xl font-semibold text-rose-950">RM {{ number_format((float) $summary['total_cost'], 2) }}</p>
                <p class="mt-1 text-xs text-rose-700">Current product cost x units</p>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 shadow-sm sm:col-span-2 xl:col-span-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Estimated profit</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-950">RM {{ number_format((float) $summary['profit_amount'], 2) }}</p>
                <p class="mt-1 text-xs text-emerald-700">Sales collected minus current product cost</p>
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
                <a href="{{ route('admin.sales.index', request()->except(['page', 'discount_page', 'show_discounts'])) }}" class="inline-flex min-h-9 items-center rounded-lg border border-amber-300 bg-white px-3 text-sm font-semibold text-amber-800">Close</a>
            </div>
            <div class="grid gap-3 border-b border-slate-200 p-5 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs font-semibold uppercase text-slate-500">Agent discount</p><p class="mt-2 text-xl font-semibold text-slate-950">RM {{ number_format((float) $summary['agent_discount_amount'], 2) }}</p></div>
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs font-semibold uppercase text-slate-500">Customer discount</p><p class="mt-2 text-xl font-semibold text-slate-950">RM {{ number_format((float) $summary['customer_discount_amount'], 2) }}</p></div>
                <div class="rounded-lg bg-amber-50 p-4"><p class="text-xs font-semibold uppercase text-amber-700">Combined discount</p><p class="mt-2 text-xl font-semibold text-amber-950">RM {{ number_format((float) $summary['discount_amount'], 2) }}</p></div>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-data-table w-full min-w-[960px] text-xs">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Sale</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Agent / Customer</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Product</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-700">Agent discount</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-700">Customer discount</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-700">Total discount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($discountDetails as $item)
                            <tr class="align-top hover:bg-slate-50">
                                <td class="px-4 py-4">
                                    <a href="{{ route('admin.sales.show', $item->posSale) }}" class="font-mono font-semibold text-[#1a73e8] hover:underline">{{ $item->posSale->sale_number }}</a>
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
                                <td class="px-4 py-4 text-right font-semibold text-slate-900">RM {{ number_format((float) $item->agent_discount_amount, 2) }}</td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-900">RM {{ number_format((float) $item->customer_discount_amount, 2) }}</td>
                                <td class="px-4 py-4 text-right font-semibold text-amber-800">RM {{ number_format((float) $item->agent_discount_amount + (float) $item->customer_discount_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-slate-500">No discounted sales match the selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($discountDetails->hasPages())
                <div class="border-t border-slate-200 p-4">{{ $discountDetails->links() }}</div>
            @endif
        </section>
    @endif

    <section class="grid gap-3 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)]" aria-label="Sales performance">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sales by business site</p><p class="mt-1 text-xs text-slate-400">{{ $periodLabel }}</p></div>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $summary['by_site']->count() }} sites</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($summary['by_site'] as $siteSale)
                    <div class="flex items-center justify-between gap-4 rounded-lg bg-slate-50 p-3">
                        <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ $siteSale->businessSite->site_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $siteSale->transaction_count }} transactions · {{ $siteSale->businessSite->city }}</p></div>
                        <p class="shrink-0 text-sm font-semibold text-slate-950">RM {{ number_format((float) $siteSale->total_amount, 2) }}</p>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No business site sales for this period.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Top product</p>
            @if ($summary['top_product'])
                <p class="mt-4 text-lg font-semibold text-amber-950">{{ $summary['top_product']->product_name }}</p>
                <p class="mt-1 font-mono text-xs text-amber-700">{{ $summary['top_product']->product_code }}</p>
                <dl class="mt-5 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-xs text-amber-700">Units sold</dt><dd class="mt-1 text-xl font-semibold text-amber-950">{{ number_format((int) $summary['top_product']->total_quantity) }}</dd></div><div><dt class="text-xs text-amber-700">Sales value</dt><dd class="mt-1 font-semibold text-amber-950">RM {{ number_format((float) $summary['top_product']->total_amount, 2) }}</dd></div></dl>
            @else
                <p class="mt-4 text-sm text-amber-800">No product sales for this period.</p>
            @endif
        </div>

        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Top agent</p>
            @if ($summary['top_agent'])
                <p class="mt-4 text-lg font-semibold text-emerald-950">{{ $summary['top_agent']->salesAgent->agt_name }}</p>
                <p class="mt-1 font-mono text-xs text-emerald-700">{{ $summary['top_agent']->salesAgent->login_id }}</p>
                <dl class="mt-5 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-xs text-emerald-700">Transactions</dt><dd class="mt-1 text-xl font-semibold text-emerald-950">{{ number_format((int) $summary['top_agent']->transaction_count) }}</dd></div><div><dt class="text-xs text-emerald-700">Sales value</dt><dd class="mt-1 font-semibold text-emerald-950">RM {{ number_format((float) $summary['top_agent']->total_amount, 2) }}</dd></div></dl>
            @else
                <p class="mt-4 text-sm text-emerald-800">No agent sales for this period.</p>
            @endif
        </div>
    </section>

    <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        <form method="GET" action="{{ route('admin.sales.index') }}" class="grid gap-3 lg:grid-cols-6">
            <input type="hidden" name="period" value="{{ $filters['period'] }}">
            <label class="lg:col-span-2">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Search</span>
                <input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Sale no., customer, agent or site..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
            </label>
            <label>
                <span class="mb-1 block text-xs font-semibold text-slate-600">Start date</span>
                <input name="start_date" type="date" value="{{ $filters['start_date'] }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
            </label>
            <label>
                <span class="mb-1 block text-xs font-semibold text-slate-600">End date</span>
                <input name="end_date" type="date" value="{{ $filters['end_date'] }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
            </label>
            <label>
                <span class="mb-1 block text-xs font-semibold text-slate-600">Business site</span>
                <select name="business_site_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    <option value="">All business sites</option>
                    @foreach ($businessSites as $site)
                        <option value="{{ $site->id }}" @selected($filters['business_site_id'] === $site->id)>{{ $site->site_name }} - {{ $site->city }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="mb-1 block text-xs font-semibold text-slate-600">Payment</span>
                <select name="payment_method" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    <option value="">All payments</option>
                    @foreach ($paymentMethods as $value => $label)
                        <option value="{{ $value }}" @selected($filters['payment_method'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex gap-2 lg:col-span-6 lg:justify-end">
                <button class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-5 text-sm font-semibold text-white">Apply filters</button>
                @if (collect($filters)->except('period')->filter()->isNotEmpty())
                    <a href="{{ route('admin.sales.index', ['period' => $filters['period']]) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700">Clear</a>
                @endif
            </div>
        </form>
    </section>

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
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <a href="{{ route('admin.sales.show', $sale) }}" class="font-mono font-semibold text-[#1a73e8] hover:underline">{{ $sale->sale_number }}</a>
                                <p class="mt-1 text-slate-500">{{ $sale->sold_at->format('d M Y, h:i A') }}</p>
                            </td>
                            <td class="px-4 py-4"><p class="font-semibold text-slate-900">{{ $sale->businessSite->site_name }}</p><p class="mt-1 text-slate-500">{{ $sale->businessSite->city }}</p></td>
                            <td class="px-4 py-4"><p class="font-semibold text-slate-900">{{ $sale->salesAgent->agt_name }}</p><p class="mt-1 text-slate-500">Logged by {{ $sale->recordedBy->agt_name }}</p></td>
                            <td class="px-4 py-4"><p class="font-medium text-slate-800">{{ $sale->customer_name ?: 'Walk-in customer' }}</p><p class="mt-1 text-slate-500">{{ $sale->customer_phone ?: 'No phone' }}</p></td>
                            <td class="px-4 py-4"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 font-semibold uppercase text-slate-700">{{ $sale->payment_method }}</span></td>
                            <td class="px-4 py-4 text-right"><p class="font-semibold text-slate-900">{{ number_format((int) $sale->total_units) }} units</p><p class="mt-1 text-slate-500">{{ $sale->items_count }} products</p></td>
                            <td class="px-4 py-4 text-right font-semibold text-slate-950">RM {{ number_format((float) $sale->total_amount, 2) }}</td>
                            <td class="px-4 py-4 text-right"><a href="{{ route('admin.sales.show', $sale) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 font-semibold text-slate-700 hover:bg-slate-50">Details</a></td>
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
            <a href="{{ route('admin.sales.show', $sale) }}" class="block rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition active:bg-slate-50">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="font-mono text-sm font-semibold text-[#1a73e8]">{{ $sale->sale_number }}</p><p class="mt-1 text-xs text-slate-500">{{ $sale->sold_at->format('d M Y, h:i A') }}</p></div>
                    <p class="text-base font-semibold text-slate-950">RM {{ number_format((float) $sale->total_amount, 2) }}</p>
                </div>
                <div class="mt-4"><p class="font-semibold text-slate-900">{{ $sale->businessSite->site_name }}</p><p class="mt-1 text-sm text-slate-500">{{ $sale->businessSite->city }}</p></div>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs font-semibold uppercase text-slate-500">Sales person</dt><dd class="mt-1 font-semibold text-slate-900">{{ $sale->salesAgent->agt_name }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs font-semibold uppercase text-slate-500">Products</dt><dd class="mt-1 font-semibold text-slate-900">{{ number_format((int) $sale->total_units) }} units</dd></div>
                </dl>
                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold uppercase text-slate-700">{{ $sale->payment_method }}</span><span class="text-xs font-semibold text-[#1a73e8]">View details →</span></div>
            </a>
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No sales match the selected filters.</div>
        @endforelse
    </section>

    {{ $sales->links() }}
</div>
@endsection
