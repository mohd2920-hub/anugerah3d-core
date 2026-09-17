@extends('admin.layouts.app')
@section('title', 'Semak Pembetulan Sesi')
@section('page_title', 'Semak Pembetulan Sesi')
@section('content')
<div class="space-y-5 rounded-xl bg-white p-5">
    <h2 class="text-lg font-semibold">{{ $operation->businessSite->site_name }} · Session #{{ $operation->id }}</h2>
    <p>Tutup asal: {{ $operation->closed_at ?? 'Masih terbuka' }} → Tutup sebenar: {{ $data['closed_at'] }}</p>
    @if(!empty($data['next_opened_at']))
        <p>Sesi pengganti bermula: {{ $data['next_opened_at'] }} · Tarikh laporan: {{ $data['next_report_date'] }}. {{ $operation->closed_at ? 'Masa tutup mengikut sesi asal.' : 'Sesi pengganti kekal terbuka.' }}</p>
    @endif
    <p>Sebab: {{ $data['reason'] }}</p>
    <h3 class="font-semibold">Jualan dipindahkan: {{ $review['late_sales']->count() }}</h3>
    @foreach($review['late_sales'] as $sale)<p>{{ $sale->sale_number }} · {{ $sale->sold_at }} · RM {{ number_format($sale->total_amount, 2) }}</p>@endforeach
    <h3 class="font-semibold">Kehadiran selepas masa tutup: {{ $review['attendances']->count() }}</h3>
    @foreach($review['attendances'] as $attendance)<p>{{ $attendance->agent?->agt_name ?? 'Ejen tidak tersedia' }} · {{ $attendance->signed_in_at }} – {{ $attendance->signed_out_at ?? 'Masih aktif' }}</p>@endforeach
    <p class="text-sm text-slate-500">Masa kehadiran asal dikekalkan. Nilai jualan, bayaran dan stok tidak berubah. Tarikh laporan jualan yang dipindahkan mengikut sesi pengganti.</p>
    <form method="POST" action="{{ route('admin.business-site-operations.closure-store', $operation) }}">
        @csrf<input type="hidden" name="token" value="{{ $token }}">
        <button class="rounded bg-blue-600 px-4 py-2 text-white">Sahkan Pembetulan</button>
        <a href="{{ route('admin.business-site-operations.show', $operation) }}" class="ml-3 text-blue-600">Kembali</a>
    </form>
</div>
@endsection
