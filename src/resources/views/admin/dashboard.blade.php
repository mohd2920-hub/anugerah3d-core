@extends('admin.layouts.app')
@section('title', 'Prestasi Perniagaan | Anugerah3D')
@section('page_title', 'Dashboard')
@section('content')
<div class="business-dashboard" id="business-dashboard" data-report-url="{{ route('admin.dashboard.data') }}" data-inventory-url="{{ route('admin.dashboard.inventory') }}" data-export-url="{{ route('admin.dashboard.export') }}">
    <script type="application/json" id="dashboard-initial">{!! \Illuminate\Support\Js::encode(['report' => $report, 'inventory' => $inventory]) !!}</script>
    <div class="intro">
        <div><div class="tag">ADMIN DASHBOARD / BUSINESS INTELLIGENCE</div><h1>Prestasi Perniagaan</h1><p>Jualan, kos, keuntungan dan nilai stok dalam satu pandangan.</p></div>
        <div class="intro-actions">
            @adminRoute('admin.customer-orders.index')
                <a class="customer-link" href="{{ route('admin.customer-orders.index') }}"><x-heroicon-o-shopping-bag aria-hidden="true" />Pesanan Pelanggan</a>
            @endadminRoute
            <span class="pill" id="period-label">{{ $report['period']['start'] }} — {{ substr($report['period']['end'], 0, 10) }}</span>
        </div>
    </div>
    <div class="dashboard-navigation">
        <nav class="section-nav" aria-label="Bahagian dashboard"><a href="#performance">Prestasi</a><a href="#analysis">Analisis</a>@if($inventory !== null)<a href="#inventory">Stok & aset</a>@endif</nav>
        <span id="report-range" class="report-range">Tempoh: {{ $report['period']['start'] }} — {{ substr($report['period']['end'], 0, 10) }}</span>
    </div>
    <form class="filters" id="performance">
        <label>Tahun<select id="year-filter" name="year">@for($year = now()->year; $year >= 2000; $year--)<option value="{{ $year }}" @selected($report['filters']['year'] === $year)>{{ $year }}</option>@endfor</select></label>
        <label>Tarikh mula<input type="date" id="start-date-filter" min="2000-01-01" max="{{ now()->toDateString() }}" value="{{ $report['period']['start'] }}" required></label>
        <label>Tarikh hingga<input type="date" id="end-date-filter" min="2000-01-01" max="{{ now()->toDateString() }}" value="{{ substr($report['period']['end'], 0, 10) }}" required></label>
        <button type="submit" class="action" id="apply-dates">Terapkan tarikh</button>
        <label>Saluran<select id="channel-filter" name="channel"><option value="all">Semua saluran dibenarkan</option>@foreach($channels as $key => $name)<option value="{{ $key }}" @selected($report['filters']['channel'] === $key)>{{ $name }}</option>@endforeach</select></label>
        @if($businessSites->isNotEmpty())<label>Business site<select id="site-filter" name="site"><option value="">Semua lokasi</option>@foreach($businessSites as $site)<option value="{{ $site->id }}" @selected((int) $report['filters']['site'] === $site->id)>{{ $site->site_name }}</option>@endforeach</select></label>@endif
        <label>Banding dengan<select id="comparison-filter" name="comparison"><option value="previous" @selected($report['filters']['comparison'] === 'previous')>Tempoh sebelumnya</option><option value="year" @selected($report['filters']['comparison'] === 'year')>Tahun sebelumnya</option></select></label>
        <button type="button" class="action" id="export-report">↓ Eksport CSV</button>
    </form>
    <div id="dashboard-status" role="status" aria-live="polite"></div>
    @if(count($channels) === 0)<div class="notice">Akses dashboard anda belum merangkumi modul jualan. Data kewangan tidak dipaparkan.</div>@endif
    <section class="metrics" aria-label="Ringkasan prestasi">
        <div class="metric" style="--accent:#368fff"><span class="icon">↗</span><div class="metric-content"><div class="metric-main"><label>JUMLAH JUALAN BERSIH</label><strong id="sales">RM {{ number_format($report['summary']['sales'],2) }}</strong></div><div class="metric-detail"><small id="sales-caption">{{ number_format($report['summary']['transactions']) }} transaksi</small><span id="sales-product-count" class="sales-product-count">{{ number_format($report['summary']['units']) }} unit produk</span></div></div></div>
        <div class="metric" style="--accent:#e9ad57"><span class="icon">≋</span><div class="metric-content"><div class="metric-main"><label>KOS DIREKODKAN / ANGGARAN</label><strong id="cost">RM {{ number_format($report['summary']['cost'],2) }}</strong></div><div class="metric-detail"><button type="button" class="cost-trigger" id="cost-open">Lihat pecahan kos →</button></div></div></div>
        <div class="metric" style="--accent:#16b68c"><span class="icon">↗</span><div class="metric-content"><div class="metric-main"><label>ANGGARAN UNTUNG</label><strong id="profit">RM {{ number_format($report['summary']['profit'],2) }}</strong></div><div class="metric-detail"><small id="margin">Kos operasi lain belum termasuk</small></div></div></div>
    </section>
    <button type="button" class="notice quality-notice" id="data-quality" aria-haspopup="dialog" hidden></button>
    <section class="panel" aria-label="Graf prestasi interaktif">
        <div class="panelhead"><div><div class="eyebrow" id="eyebrow">THE BIG PICTURE</div><h2 id="chart-title">Prestasi Bulanan</h2><p id="chart-subtitle">Lihat pertumbuhan. Fahami kos. Kenal pasti peluang.</p></div><div class="toolbar"><button type="button" id="previous-month" hidden aria-label="Bulan sebelumnya">←</button><button type="button" id="next-month" hidden aria-label="Bulan seterusnya">→</button><button type="button" id="back-monthly" hidden>← Bulanan</button><button type="button" id="replay">↻ Main animasi</button></div></div>
        <div class="charttop"><span class="axislabel">NILAI DALAM RM</span><div class="legend"><span><i class="dot" style="--c:#429aff"></i>POS</span><span><i class="dot" style="--c:#a192de"></i>Order Ejen</span><span><i class="dot" style="--c:#79a8bd"></i>Pelanggan</span><span><i class="dot" style="--c:#e7b46b"></i>Kos</span><span><i class="dot" style="--c:#42e1b5"></i>Untung</span></div></div>
        <div class="chartwrap"><svg id="chart" viewBox="0 0 1120 365" aria-label="Graf jualan, kos dan untung"></svg><div class="tooltip" id="tooltip" role="status"></div></div>
        <div class="panelfoot"><span id="chart-hint">Klik bulan untuk melihat prestasi harian.</span><span><i class="pulse"></i><span id="updated-at">Data rekod sebenar</span></span></div>
    </section>
    <section class="insights"><div class="insight"><label>Jualan tertinggi</label><strong id="best">—</strong><small id="best-value"></small></div><div class="insight"><label>Purata jualan</label><strong id="average">—</strong><small id="average-caption"></small></div><div class="insight"><label>Margin keuntungan</label><strong id="margin-insight">—</strong><small>Selepas kos yang tersedia</small></div></section>
    <div id="analysis">
        <div class="twocol"><section class="card summary-card" id="summary"></section><section class="card"><div class="cardhead"><div><h3>Sasaran jualan</h3><small id="target-period"></small></div><span class="badge" id="target-badge">Simulasi</span></div><div class="target-layout"><div class="ring"><svg viewBox="0 0 120 120" aria-hidden="true"><circle class="track" cx="60" cy="60" r="50"/><circle class="fill" id="target-ring" cx="60" cy="60" r="50"/></svg><strong id="target-percent">—</strong></div><div><label for="target-value" class="subtle">Sasaran bulanan (RM)</label><br><input id="target-value" type="number" min="1" max="1000000000" step="100" placeholder="Masukkan sasaran"><p id="target-remaining">Masukkan sasaran untuk melihat pencapaian.</p><small>Simulasi pada paparan ini sahaja. Tidak disimpan.</small></div></div></section></div>
        <div class="section-heading"><div><div class="tag">REVENUE MIX</div><h2>Dari mana jualan datang?</h2><p>Klik saluran untuk menapis prestasi.</p></div></div><div class="channel-grid" id="channels"></div>
        <div class="twocol"><section class="card" id="cost-breakdown"></section><section class="card" id="site-ranking"></section></div>
        <div class="twocol"><section class="card" id="product-ranking"></section><section class="card" id="transaction-preview"></section></div>
    </div>
    @if($inventory !== null)
    <section class="stock-shell" id="inventory" style="margin-top:38px">
        <template id="stock-edit-icon"><x-heroicon-o-pencil-square aria-hidden="true" /></template>
        <div class="section-heading" style="margin-top:0"><div><div class="tag">INVENTORY INTELLIGENCE</div><h2>Stok hari ini. Potensi esok.</h2><p>Stok pusat semasa, termasuk produk tersembunyi. Tidak mengikut tarikh / lokasi jualan.</p></div><span class="pill">Stok pusat · Baca sahaja</span></div>
        <form class="stockfilters" id="stock-filters"><label>Jenis produk<select name="stock_type"><option value="all">Semua jenis</option><option value="normal">Normal</option><option value="clicker">Clicker</option></select></label><label>Cari produk<input name="stock_search" type="search" maxlength="100" placeholder="Nama, kod atau casing…"></label><label>Status<select name="stock_status"><option value="all">Semua status</option><option value="low">Rendah (1–4)</option><option value="out">Habis</option><option value="healthy">Mencukupi (5+)</option></select></label><label class="stock-toggle"><input type="checkbox" role="switch" name="stock_include_discontinued" value="1" @checked(request()->boolean('stock_include_discontinued'))><span>Sertakan produk dihentikan</span></label><button type="submit" class="action">Cari</button></form>
        <div class="stock-metrics" id="stock-metrics"></div><button type="button" class="notice quality-notice" id="stock-quality" aria-haspopup="dialog" hidden></button>
        <div class="tablewrap"><table><thead><tr><th>Produk / Varian</th><th>Tersedia</th><th>Diperuntukkan</th><th>Kos / unit</th><th>Aset tersedia</th><th>Harga jualan</th><th>Potensi untung kasar</th><th>Status</th></tr></thead><tbody id="stock-rows"></tbody></table></div><div class="pagination" id="stock-pagination"></div>
        <div class="stock-note">Nilai aset tersedia = stok tersedia × kos seunit. Stok diperuntukkan telah ditolak daripada baki; tidak ditolak kali kedua. Potensi untung belum direalisasi dan belum menolak diskaun, komisen atau kos operasi. Clicker tanpa pecahan saiz tidak diberi nilai kos rekaan.</div>
        <div class="stock-bottom"><div id="stock-alerts"></div><div id="stock-leaders"></div></div>
    </section>
    @endif
    <details class="card calculation-notes"><summary>Asas pengiraan & kelengkapan data</summary><ul>@foreach($report['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></details>
    <dialog id="dashboard-modal" aria-labelledby="modal-title"><div class="dialog-head"><div><div class="tag" style="font-size:9px;margin-bottom:6px">BUTIRAN REKOD</div><h2 id="modal-title"></h2></div><button type="button" id="close-modal" aria-label="Tutup butiran">×</button></div><div class="dialog-body" id="modal-body"></div></dialog>
    <noscript><p class="notice">Aktifkan JavaScript untuk graf, penapis dan pecahan interaktif. Ringkasan angka di atas masih berdasarkan rekod sebenar.</p></noscript>
</div>
@endsection
