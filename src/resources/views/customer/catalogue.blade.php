@extends('customer.layout')
@section('header_actions')
<a href="{{ auth('agent')->check() ? route('agent.dashboard') : '#catalogue-products' }}" @if(!auth('agent')->check()) data-customer-back @endif class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600">
    <svg class="h-4 w-4" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Kembali
</a>
@endsection
@section('content')
<p class="mb-4 text-sm text-slate-600">Pilih produk dan tempah tanpa daftar. Pesanan dan pembayaran diuruskan oleh admin Anugerah3D.</p>
<div id="catalogue-products">
@include('agent.partials.product-ordering')
</div>
@endsection
