<nav aria-label="Halaman produk" class="flex w-fit max-w-full flex-wrap gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
    @foreach(['admin.products.index' => 'Stok Produk', 'admin.products.statistics' => 'Statistik Produk'] as $routeName => $label)
        <a href="{{ route($routeName, ['search' => $search ?: null, 'include_hidden' => $includeHidden ? 1 : null]) }}" @if(request()->routeIs($routeName) && $stockFilter !== 'empty') aria-current="page" @endif class="rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs($routeName) && $stockFilter !== 'empty' ? 'bg-blue-700 text-white' : 'text-slate-600 hover:bg-blue-50' }}">{{ $label }}</a>
    @endforeach
    <a href="{{ route('admin.products.index', ['stock' => 'empty', 'include_hidden' => 1]) }}" @if($stockFilter === 'empty') aria-current="page" @endif class="rounded-lg px-4 py-2 text-sm font-semibold {{ $stockFilter === 'empty' ? 'bg-red-700 text-white' : 'text-red-700 hover:bg-red-50' }}">Stok Habis</a>
</nav>
