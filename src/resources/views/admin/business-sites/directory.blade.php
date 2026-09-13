@extends('admin.layouts.app')
@section('title', 'Sales Summary Lokasi | Anugerah3D Admin')
@section('page_title', 'Business Site Sales Summary')
@section('content')
<div class="site-workspace space-y-6">
    @include('admin.business-sites._navigation', ['active' => 'summary'])
    <section class="site-hero"><div><p class="site-eyebrow">SALES / LOCATION DIRECTORY</p><h2>Satu lokasi. Satu ringkasan.</h2><p>Pilih lokasi untuk melihat jualan dan sejarah sesi pada halaman khususnya.</p></div><span class="site-hero-tag">03 / SALES SUMMARY</span></section>
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($businessSites as $site)
            <a class="site-directory-card" href="{{ route('admin.business-sites.summary', $site) }}">
                <div class="flex items-center justify-between gap-3"><span class="site-location-mark">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span @class(['site-live' => $site->isOpen(), 'site-closed' => !$site->isOpen()])>{{ $site->isOpen() ? 'Sedang Beroperasi' : 'Ditutup' }}</span></div>
                <h2>{{ $site->site_name }}</h2><p>{{ $site->city }}</p>
                <div class="site-card-link"><span>{{ $site->operations_count }} sesi operasi</span><span>Buka ringkasan ↗</span></div>
            </a>
        @empty
            <div class="site-empty">Belum ada lokasi. Tambah lokasi melalui Business Site Control.</div>
        @endforelse
    </div>
    {{ $businessSites->links() }}
</div>
@endsection
