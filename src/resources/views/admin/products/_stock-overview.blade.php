@php
    $stockCards = [
        'empty' => ['Stok Habis', 'item / variasi · baki 0', 'text-red-700 border-red-200 bg-red-50'],
        'critical' => ['Stok Kritikal', 'item / variasi · baki 1–4', 'text-amber-700 border-amber-200 bg-amber-50'],
        'healthy' => ['Stok Mencukupi', 'item / variasi · baki 5+', 'text-emerald-700 border-emerald-200 bg-emerald-50'],
        'all' => ['Jumlah Produk', 'semua produk', 'text-blue-700 border-blue-200 bg-blue-50'],
        'hidden' => ['Disembunyikan', 'tidak termasuk produk dihentikan', 'text-slate-700 border-slate-200 bg-slate-50'],
    ];
@endphp
<section aria-label="Statistik stok" class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($stockCards as $key => [$label, $description, $color])
            @if($key === 'all')
                <section aria-label="Jumlah dan katalog produk" class="grid grid-cols-2 gap-2 rounded-2xl border border-blue-200 bg-blue-50 p-2">
                    @foreach(['all' => ['Jumlah Produk', 'semua produk'], 'catalogue' => ['Dalam Katalog Agen', 'produk yang agen boleh lihat']] as $category => [$heading, $caption])
                        <a href="{{ route('admin.products.index', ['search' => $search ?: null, 'stock' => $category]) }}" class="min-w-0 rounded-xl p-2 text-blue-700 transition hover:bg-blue-100 focus-visible:outline-blue-600">
                            <h3 class="text-sm font-bold">{{ $heading }}</h3>
                            <p class="my-2 text-3xl font-extrabold">{{ number_format($stockStats[$category]) }}</p>
                            <p class="text-xs">{{ $caption }}</p>
                        </a>
                    @endforeach
                </section>
            @else
            <a href="{{ route('admin.products.index', ['search' => $search ?: null, 'stock' => $key, 'include_hidden' => $includeHidden ? 1 : null]) }}" @if($stockFilter === $key) aria-current="page" @endif class="rounded-2xl border p-4 transition hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-600 {{ $color }} {{ $stockFilter === $key ? 'ring-2 ring-blue-600' : '' }}">
                <h3 class="text-sm font-bold">{{ $label }}</h3><p class="my-2 text-3xl font-extrabold">{{ number_format($stockStats[$key]) }}</p><p class="text-xs">{{ $description }}</p>
            </a>
            @endif
        @endforeach
        @if($discontinuationAvailable)
            <section aria-label="Produk Dihentikan" class="rounded-2xl border border-cyan-200 bg-white p-4">
                <h3 class="px-2 pb-2 pt-1 text-sm font-bold text-slate-900">Dihentikan</h3>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('admin.products.index', ['search' => $search ?: null, 'stock' => 'discontinued']) }}"
                        @if($stockFilter === 'discontinued') aria-current="page" @endif
                        class="flex items-center justify-between gap-4 rounded-xl bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-600 {{ $stockFilter === 'discontinued' ? 'ring-2 ring-cyan-600' : '' }}">
                        <span>Semua</span><strong class="text-lg">{{ number_format($stockStats['discontinued']) }}</strong>
                    </a>
                    <a href="{{ route('admin.products.index', ['search' => $search ?: null, 'stock' => 'discontinued-stock']) }}"
                        @if($stockFilter === 'discontinued-stock') aria-current="page" @endif
                        aria-label="Dihentikan — Masih Ada Stok: {{ $discontinuedStockCount }} produk"
                        class="flex items-center justify-between gap-4 rounded-xl bg-cyan-50 px-3 py-3 text-sm font-semibold text-cyan-900 transition hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-600 {{ $stockFilter === 'discontinued-stock' ? 'ring-2 ring-cyan-600' : '' }}">
                        <span>Masih Ada Stok</span><strong class="text-lg">{{ number_format($discontinuedStockCount) }}</strong>
                    </a>
                </div>
                <p class="px-2 pt-2 text-xs text-slate-500">Bilangan produk, termasuk yang tersembunyi.</p>
            </section>
        @endif
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.products.index', ['search' => $search ?: null, 'stock' => 'low', 'include_hidden' => $includeHidden ? 1 : null]) }}" class="rounded-xl bg-blue-700 px-4 py-3 text-sm font-bold text-white hover:bg-blue-800">Lihat Semua Stok Bawah 5 <span class="ml-2 rounded-full bg-white/20 px-2 py-1">{{ $stockStats['empty'] + $stockStats['critical'] }}</span></a>
        <a href="{{ route('admin.products.index', ['search' => $search ?: null]) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">Semua Produk</a>

    </div>
    <p class="text-xs text-slate-500">Statistik stok: {{ $includeHidden ? 'termasuk produk tersembunyi' : 'produk yang kelihatan dalam katalog sahaja' }}. Produk dihentikan dikecualikan; lihat melalui bahagian Dihentikan. “Masih Ada Stok” ialah sebahagian daripada jumlah “Semua”. Produk biasa dikira sekali; Clicker dikira mengikut casing dan saiz huruf yang mempunyai harga serta kombinasi.</p>
</section>
