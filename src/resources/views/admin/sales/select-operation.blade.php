@extends('admin.layouts.app')
@section('title', 'Tambah Jualan')
@section('page_title', 'Tambah Jualan')
@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <a href="{{ route('admin.sales.index', ['single_date' => $date->toDateString()]) }}" class="text-sm font-semibold text-blue-600">Kembali ke Sales</a>
    @if ($errors->any())
        <div role="alert" class="rounded-lg bg-red-50 p-4 text-red-700">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <form method="GET" action="{{ route('admin.sales.add') }}" class="flex flex-wrap items-end gap-4">
        <label class="text-sm font-medium">Tarikh jualan<input required type="date" name="single_date" max="{{ now()->toDateString() }}" value="{{ $date->toDateString() }}" class="mt-1 block w-full rounded-lg border-slate-300"></label>
        <label class="min-w-0 text-sm font-medium">Business site<select name="business_site_id" class="mt-1 block w-full max-w-full rounded-lg border-slate-300"><option value="">Semua lokasi</option>@foreach ($businessSites as $site)<option value="{{ $site->id }}" @selected((string) $businessSiteId === (string) $site->id)>{{ $site->site_name }}</option>@endforeach</select></label>
        <button type="submit" class="min-h-11 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Cari</button>
    </form>
    <section class="divide-y divide-slate-200 border-y border-slate-200">
        @forelse ($operations as $operation)
            <div class="flex flex-wrap items-center justify-between gap-4 py-4">
                <div class="min-w-0 break-words">
                    <h2 class="text-base font-semibold text-slate-900">{{ $operation->businessSite->site_name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Sesi #{{ $operation->id }}: {{ $operation->opened_at->format('d M Y H:i') }} - {{ $operation->closed_at?->format('d M Y H:i') ?? 'Masih dibuka' }}</p>
                </div>
                <a href="{{ route('admin.sales.create', ['businessSiteOperation' => $operation, 'single_date' => $date->toDateString()]) }}" class="inline-flex min-h-11 shrink-0 items-center rounded-lg border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">Tambah Jualan</a>
            </div>
        @empty
            <p class="py-8 text-sm text-slate-500">Tiada sesi operasi untuk tarikh dan lokasi ini.</p>
        @endforelse
    </section>
    {{ $operations->links() }}
</div>
@endsection
