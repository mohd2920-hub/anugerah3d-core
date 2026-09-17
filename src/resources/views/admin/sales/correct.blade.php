@extends('admin.layouts.app')
@section('title', $sale ? 'Edit Sale' : 'Add Missing Sale')
@section('page_title', $sale ? 'Edit Sale' : 'Add Missing Sale')
@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    @adminRoute('admin.sales.show')
<a class="text-sm font-semibold text-blue-600" href="{{ $sale ? route('admin.sales.show', $sale) : route('admin.business-site-operations.show', $operation) }}">← Back to details</a>
@endadminRoute
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-lg font-semibold">{{ $operation->businessSite->site_name }} · Session #{{ $operation->id }}</h2>
        <p class="mt-2 text-sm text-slate-500">{{ $sale?->sale_number ?? 'A new receipt number will be assigned when saved.' }} · {{ $operation->opened_at->format('d M Y H:i') }} – {{ $operation->closed_at?->format('d M Y H:i') ?? 'Now' }}</p>
        <p class="mt-2 text-sm text-slate-500">Existing product prices are preserved. New products use their current selling price. Review the calculated totals before saving.</p>
    </div>
    @if ($errors->any())<div role="alert" class="rounded-lg bg-red-50 p-4 text-red-700">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ $sale ? route('admin.sales.preview', $sale) : route('admin.sales.preview-missing', $operation) }}" class="space-y-5" data-sale-correction>
        @csrf
        <script type="application/json" data-pos-clicker-catalog>@json($posClickerCatalog ?? [])</script>
        <input type="hidden" name="action" value="{{ $sale ? 'correct' : 'missing' }}">
        <div class="grid gap-4 rounded-xl bg-white p-5 ring-1 ring-slate-200 sm:grid-cols-2">
            <label class="text-sm font-medium">Sales person<select required name="sales_agent_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Select sales person</option>@foreach ($agents as $agent)<option value="{{ $agent->id }}" @selected((string) old('sales_agent_id', $sale?->sales_agent_id) === (string) $agent->id)>{{ $agent->agt_name }}</option>@endforeach</select></label>
            <label class="text-sm font-medium">Tarikh dan masa jualan<input required type="datetime-local" step="1" name="sold_at" min="{{ $operation->opened_at->format('Y-m-d\TH:i:s') }}" max="{{ ($operation->closed_at ?? now())->min(now())->format('Y-m-d\TH:i:s') }}" value="{{ old('sold_at', ($sale?->sold_at ?? $defaultSoldAt ?? $operation->closed_at ?? now())->format('Y-m-d\TH:i:s')) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
            @foreach (['customer_name' => 'Customer name', 'customer_phone' => 'Customer phone', 'customer_email' => 'Customer email', 'remark' => 'Sale remark', 'payment_remark' => 'Payment remark'] as $field => $label)
                <label class="text-sm font-medium">{{ $label }}<input type="{{ $field === 'customer_email' ? 'email' : 'text' }}" name="{{ $field }}" value="{{ old($field, $sale?->$field) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
            @endforeach
            <label class="text-sm font-medium">Payment method<select required name="payment_method" class="mt-1 w-full rounded-lg border-slate-300">@foreach ($paymentMethods as $key => $label)<option value="{{ $key }}" @selected(old('payment_method', $sale?->payment_method) === $key)>{{ $label }}</option>@endforeach</select></label>
        </div>
        <div class="space-y-4 rounded-xl bg-white p-5 ring-1 ring-slate-200">
            <h3 class="font-semibold">Products</h3>
            <div data-correction-items class="space-y-3">
                @foreach (old('items', $sale?->items->map(fn ($item) => $item->correctionInput())->all() ?? [['product_id' => '', 'quantity' => 1, 'discount_amount' => 0]]) as $index => $item)
                    @include('admin.sales._correction-item')
                @endforeach
            </div>
            <button type="button" data-add-correction-item class="rounded-lg border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700">Add product</button>
            <template data-correction-template>@include('admin.sales._correction-item', ['index' => '__INDEX__', 'item' => ['product_id' => '', 'quantity' => 1, 'discount_amount' => 0]])</template>
        </div>
        <label class="block rounded-xl bg-white p-5 text-sm font-semibold ring-1 ring-slate-200">Reason for correction (required)<textarea required minlength="5" maxlength="2000" name="reason" rows="3" class="mt-2 w-full rounded-lg border-slate-300">{{ old('reason') }}</textarea></label>
        <button class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white">Preview Before / After</button>
    </form>
</div>
@endsection
