    <div id="sales-filter-modal" class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/60 p-0 backdrop-blur-sm sm:items-center sm:p-5" data-sales-filter-modal data-open-on-load="{{ $errors->any() ? 'true' : 'false' }}" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="sales-filter-title">
        <button type="button" class="absolute inset-0" data-close-sales-filters tabindex="-1" aria-label="Close sales filters"></button>
        <section class="relative max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#1a73e8]">Sales search</p>
                    <h2 id="sales-filter-title" class="mt-1 text-xl font-semibold text-slate-950">Search and filter sales</h2>
                    <p class="mt-1 text-sm text-slate-500">Refine the current {{ strtolower($periodLabel) }} sales view.</p>
                </div>
                <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100 text-xl text-slate-600 transition hover:bg-slate-200" data-close-sales-filters aria-label="Close sales filters">×</button>
            </div>

            <div class="p-5">
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
                @endif

                @adminRoute('admin.sales.index')
<form method="GET" action="{{ route($salesPageRoute) }}" class="grid gap-4 lg:grid-cols-6">
                    <input type="hidden" name="period" value="{{ $filters['period'] }}">
                    <label class="lg:col-span-2">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">Search</span>
                        <input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Sale no., customer, agent or site..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-slate-600">Start date</span>
                        <input name="start_date" type="date" value="{{ $filters['start_date'] }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-slate-600">End date</span>
                        <input name="end_date" type="date" value="{{ $filters['end_date'] }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-slate-600">Business site</span>
                        <select name="business_site_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                            <option value="">All business sites</option>
                            @foreach ($businessSites as $site)
                                <option value="{{ $site->id }}" @selected($filters['business_site_id'] === $site->id)>{{ $site->site_name }} - {{ $site->city }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-slate-600">Payment</span>
                        <select name="payment_method" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                            <option value="">All payments</option>
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}" @selected($filters['payment_method'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end lg:col-span-6">
                        @if ($activeFilterCount > 0 || $filters['start_date'] !== null || $filters['end_date'] !== null)
                            @adminRoute('admin.sales.index')
<a href="{{ route($salesPageRoute, ['period' => $filters['period']]) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Clear filters</a>
@endadminRoute
                        @endif
                        <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50" data-close-sales-filters>Cancel</button>
                        <button class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-5 text-sm font-semibold text-white transition hover:bg-blue-700">Apply filters</button>
                    </div>
                </form>
@endadminRoute
            </div>
        </section>
    </div>

