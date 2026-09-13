@extends('admin.layouts.app')
@section('title', 'Statistik Business Sites | Anugerah3D Admin')
@section('page_title', 'Business Site Statistik')
@section('content')
<div class="site-workspace space-y-6">
    @include('admin.business-sites._navigation', ['active' => 'statistics'])
    <section class="site-hero"><div><p class="site-eyebrow">INSIGHTS / BUSINESS SITES</p><h2>Prestasi jelas. Keputusan tepat.</h2><p>Semak setiap lokasi secara berasingan. Pilih lokasi untuk membandingkan atau melihat jumlah gabungan.</p></div><span class="site-hero-tag">02 / STATISTIK</span></section>
    <form method="GET" action="{{ route('admin.business-sites.statistics') }}" class="site-filter-panel" data-site-report-filter>
        @include('admin.business-sites._filters')
        <fieldset class="mt-5"><legend class="mb-3 text-sm font-semibold text-slate-800">Pilih lokasi <span class="font-normal text-slate-500">· Tanpa pilihan, semua lokasi dipaparkan berasingan</span> <button type="button" class="ml-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 disabled:opacity-50" data-select-all-sites hidden>Pilih Semua</button></legend>
            <div class="site-location-options">@foreach ($businessSites as $site)<label><input type="checkbox" name="site_ids[]" value="{{ $site->id }}" @checked(in_array($site->id, $filters['site_ids'] ?? []))><span>{{ $site->site_name }}</span></label>@endforeach</div>
        </fieldset>
        <div class="site-mode-actions">
            <button class="site-button {{ $mode === 'separate' ? '' : 'site-button-secondary' }}" name="mode" value="separate">Paparan Berasingan</button>
            <button class="site-button {{ $mode === 'compare' ? '' : 'site-button-secondary' }}" name="mode" value="compare">Bandingkan Lokasi</button>
            <button class="site-button {{ $mode === 'combined' ? '' : 'site-button-secondary' }}" name="mode" value="combined">Gabungkan Lokasi Dipilih</button>
        </div>
    </form>
    @include('admin.business-sites._period')
    <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-xl font-bold text-slate-900">{{ match($mode) {'combined' => 'Statistik Gabungan', 'compare' => 'Perbandingan Lokasi', default => 'Paparan Berasingan'} }}</h2><span class="site-closed">{{ $summaries->count() }} lokasi · {{ $mode === 'combined' ? 'Gabungan dipilih' : 'Angka setiap lokasi' }}</span></div>
    @if ($mode === 'combined')
        <section class="site-combined" data-combined-summary>
            <p class="mb-4 font-semibold text-blue-900">{{ $summaries->pluck('site_name')->join(' + ') }}</p>
            <x-admin.business-site-metrics :summary="$combinedSummary" />
            <a class="site-card-link" href="{{ route('admin.business-sites.statistics', array_merge($filters, ['mode' => 'separate'])) }}">Kembali ke Paparan Berasingan →</a>
        </section>
        <h3 class="text-lg font-semibold">Pecahan setiap lokasi</h3>
    @endif
    @if ($mode === 'compare')
        <div data-horizontal-scroll-controls class="overflow-x-auto rounded-2xl border border-slate-200 bg-white"><table class="w-full min-w-[700px] text-left text-sm"><thead class="bg-slate-50"><tr><th class="p-4">Ukuran</th>@foreach($summaries as $summary)<th class="p-4"><a href="{{ route('admin.business-sites.summary', ['businessSite' => $summary->id] + collect($filters)->except(['mode', 'site_ids'])->all()) }}">{{ $summary->site_name }} ↗</a></th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100">
        @foreach (['sales_total' => 'Jualan bersih (RM)', 'sales_count' => 'Transaksi', 'items_sold' => 'Unit terjual', 'capital_total' => 'Modal (RM)', 'gross_profit_total' => 'Untung kasar (RM)', 'operation_days' => 'Hari Operasi', 'operations_count' => 'Sesi Perniagaan', 'open_count' => 'Sesi sedang beroperasi'] as $metric => $label)
            <tr><th class="p-4 font-medium text-slate-500">{{ $label }}</th>@foreach($summaries as $summary)<td class="p-4 font-semibold tabular-nums">{{ number_format($metric === 'gross_profit_total' ? $summary->sales_total - $summary->capital_total : $summary->$metric, in_array($metric, ['sales_total', 'capital_total', 'gross_profit_total']) ? 2 : 0) }}</td>@endforeach</tr>
        @endforeach
        </tbody></table></div>
    @else
        <div class="space-y-5">@forelse($summaries as $summary)
            <article class="site-stat-card"><div class="site-stat-heading"><div><p class="site-eyebrow">{{ $summary->city }}</p><h3>{{ $summary->site_name }}</h3></div><a class="site-text-link" href="{{ route('admin.business-sites.summary', ['businessSite' => $summary->id] + collect($filters)->except(['mode', 'site_ids'])->all()) }}">Buka ringkasan ↗</a></div>
            @if($summary->open_count)<p class="px-5 pb-3 text-xs font-semibold text-emerald-700">{{ $summary->open_count }} sesi sedang beroperasi · Angka setakat semakan</p>@endif
            <x-admin.business-site-metrics :summary="$summary" /></article>
        @empty<div class="site-empty">Belum ada lokasi untuk dipaparkan.</div>@endforelse</div>
    @endif
</div>
@endsection
