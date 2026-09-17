    @adminRoute('admin.sales.add')
    <div class="flex justify-end">
        <a href="{{ route('admin.sales.add', array_filter(['single_date' => $filters['start_date'] ?? ($filters['period'] === 'yesterday' ? now()->subDay()->toDateString() : now()->toDateString()), 'business_site_id' => $filters['business_site_id']])) }}" class="inline-flex min-h-11 items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Tambah Jualan</a>
    </div>
    @endadminRoute
    <section class="overflow-x-auto rounded-lg bg-white p-2 shadow-sm ring-1 ring-slate-200/70" aria-label="Sales period">
        <div class="flex min-w-max gap-2">
            @foreach ($periodOptions as $value => $label)
                @adminRoute('admin.sales.index')
<a href="{{ route($salesPageRoute, array_merge(request()->except(['period', 'page', 'start_date', 'end_date', 'single_date']), ['period' => $value])) }}" @class([
                    'rounded-lg px-4 py-2.5 text-sm font-semibold transition',
                    'bg-[#1a73e8] text-white shadow-sm' => $filters['period'] === $value && !$filters['start_date'],
                    'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' => $filters['period'] !== $value || $filters['start_date'],
                ])>{{ $label }}</a>
@endadminRoute
            @endforeach
            <div class="ml-1 border-l border-slate-200 pl-3">
                <button type="button" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-[#1a73e8]" data-open-sales-filters aria-haspopup="dialog" aria-controls="sales-filter-modal" aria-expanded="false">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    Search / Filters
                    @if ($activeFilterCount > 0)
                        <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-[#1a73e8] px-1.5 py-0.5 text-[11px] font-bold text-white">{{ $activeFilterCount }}</span>
                    @endif
                </button>
            </div>
            <form method="GET" action="{{ route($salesPageRoute) }}" class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3" aria-label="Carian jualan satu hari">
                @foreach (['search', 'business_site_id', 'payment_method'] as $key)
                    @if ($filters[$key])<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif
                @endforeach
                <label for="sales-single-date" class="text-sm font-semibold text-slate-700">Tarikh Tertentu (Sesi)</label>
                <input id="sales-single-date" name="single_date" type="date" required value="{{ $filters['start_date'] === $filters['end_date'] ? $filters['start_date'] : '' }}" class="min-h-10 rounded-md border-0 bg-transparent px-2 text-sm text-slate-800 outline-none focus:ring-2 focus:ring-blue-200">
                <button type="submit" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Cari</button>
            </form>
        </div>
    </section>
