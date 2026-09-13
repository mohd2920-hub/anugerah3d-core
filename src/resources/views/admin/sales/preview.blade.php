@extends('admin.layouts.app')
@section('title', 'Review Sale Correction')
@section('page_title', 'Review Sale Correction')
@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    @adminRoute('admin.sales.edit')
<a class="text-sm font-semibold text-blue-600" href="{{ $sale ? route('admin.sales.edit', $sale) : route('admin.sales.create', $operation) }}">← Return to form</a>
@endadminRoute
    @if ($errors->any())<div role="alert" class="rounded-lg bg-red-50 p-4 text-red-700">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Reason: {{ $data['reason'] }}</p>
    <div class="grid gap-5 md:grid-cols-2">@foreach (['before' => 'Before', 'after' => 'After'] as $key => $label)<section class="rounded-xl bg-white p-5 ring-1 ring-slate-200"><h2 class="mb-4 text-lg font-bold">{{ $label }}</h2>@include('admin.sales._snapshot', ['snapshot' => $comparison[$key]])</section>@endforeach</div>
    @if (!empty($comparison['stock_changes']))
        <div class="rounded-xl bg-blue-50 p-4 text-sm"><p class="font-semibold">Perubahan stok casing</p>
            @foreach ($comparison['stock_changes'] as $change)<p>{{ $change['casing_id'] ? 'Casing #'.$change['casing_id'].' · '.$change['character_count'].' huruf' : 'Produk #'.$change['product_id'] }}: {{ $change['before'] }} → {{ $change['after'] }} unit</p>@endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('admin.sale-corrections.store') }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><button class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white">Confirm and save {{ $data['action'] === 'void' ? 'Void Sale' : 'correction' }}</button></form>
</div>
@endsection
