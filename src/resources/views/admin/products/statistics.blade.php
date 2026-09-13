@extends('admin.layouts.app')
@section('title', 'Statistik Produk | Anugerah3D Admin')
@section('page_title', 'Statistik Produk')
@section('content')
<div class="space-y-5">
    @include('admin.products._navigation')
    <div><h2 class="text-xl font-bold text-slate-900">Statistik Produk</h2><p class="mt-1 text-sm text-slate-500">Pilih kategori untuk melihat senarai stok yang berkaitan.</p></div>
    <form method="get" action="{{ route('admin.products.statistics') }}" class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <label for="statistics-search" class="text-sm font-semibold">Carian statistik</label>
        <input id="statistics-search" type="search" name="search" value="{{ $search }}" placeholder="Nama atau kod produk…" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="include_hidden" value="1" @checked($includeHidden)>Termasuk produk tersembunyi</label>
        <button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Cari</button>
        @if($search !== '' || $includeHidden)<a href="{{ route('admin.products.statistics') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Reset carian</a>@endif
    </form>
    @if($search !== '')<p role="status" class="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-900">Statistik untuk carian: <strong>{{ $search }}</strong> · {{ number_format($stockStats['all']) }} produk sepadan.</p>@endif
    @include('admin.products._stock-overview')
</div>
@endsection
