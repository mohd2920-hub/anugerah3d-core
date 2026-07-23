@extends('agent.layouts.app')

@section('title', 'POS | Anugerah3D Agent')
@section('page_title', 'Point of Sale')

@section('content')
<div class="space-y-5" data-pos-root>
    @if (session('success'))<div class="rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif

    @if ($activeSession)
        <section class="overflow-hidden rounded-3xl bg-[#17324d] p-5 text-white shadow-xl">
            <div class="flex items-start justify-between gap-4"><div><p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">Checked in at</p><h2 class="mt-1 text-xl font-extrabold">{{ $activeSession->businessSite->site_name }}</h2><p class="text-sm text-slate-300">{{ $activeSession->businessSite->city }}</p></div><form method="POST" action="{{ route('agent.pos.sign-out') }}">@csrf<button class="rounded-full border border-white/20 px-3 py-2 text-xs font-bold">Check out</button></form></div>
            <div class="mt-5 rounded-2xl bg-white/10 p-4"><p class="text-[10px] font-bold uppercase tracking-widest text-slate-300">Checked in for</p><p class="mt-1 font-mono text-3xl font-black" data-pos-timer data-signed-in-at="{{ $activeSession->signed_in_at->toIso8601String() }}">00:00:00</p></div>
        </section>
    @else
        <section class="rounded-3xl bg-white p-5 shadow-sm">
            <div class="grid h-14 w-14 place-items-center rounded-2xl bg-orange-100 text-[#d95419]"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18v12H3z"/><path d="M7 12h.01M17 12h.01"/><circle cx="12" cy="12" r="2"/></svg></div>
            <h2 class="mt-4 text-xl font-extrabold text-[#17324d]">Check in to POS</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Choose your assigned business site. Your session remains active until you check out.</p>
            @if ($businessSites->isNotEmpty())
                <form method="POST" action="{{ route('agent.pos.sign-in') }}" class="mt-5 space-y-3">@csrf<select name="business_site_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm"><option value="">Choose business site</option>@foreach ($businessSites as $site)<option value="{{ $site->id }}">{{ $site->site_name }} · {{ $site->city }}</option>@endforeach</select>@error('business_site_id')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror<button class="h-12 w-full rounded-2xl bg-[#e7682b] text-sm font-extrabold text-white">Check in</button></form>
            @else
                <div class="mt-5 rounded-2xl bg-amber-50 p-4 text-sm font-semibold text-amber-800">No business site has been assigned to your account. Please contact admin.</div>
            @endif
        </section>
    @endif

    <div class="grid grid-cols-2 rounded-2xl bg-slate-200/70 p-1">
        <a href="{{ route('agent.pos.index') }}" @class(['rounded-xl px-3 py-2.5 text-center text-xs font-extrabold', 'bg-white text-[#17324d] shadow-sm' => request('tab') !== 'history', 'text-slate-500' => request('tab') === 'history'])>New Sale</a>
        <a href="{{ route('agent.pos.index', ['tab' => 'history']) }}" @class(['rounded-xl px-3 py-2.5 text-center text-xs font-extrabold', 'bg-white text-[#17324d] shadow-sm' => request('tab') === 'history', 'text-slate-500' => request('tab') !== 'history'])>Sale History</a>
    </div>

    @if (request('tab') !== 'history')
        @if ($activeSession)
            @include('agent.pos._sale-form', ['action' => route('agent.pos.sales.store'), 'submitLabel' => 'Complete sale'])
        @else
            <div class="rounded-3xl border border-dashed border-slate-300 p-8 text-center text-sm font-semibold text-slate-500">Check in to a business site before recording a sale.</div>
        @endif
    @else
        <div class="space-y-3">
            @forelse ($sales as $sale)
                @php
                    $grossTotal = $sale->items->sum(fn ($item) => (float) $item->unit_price * (int) $item->quantity);
                    $discountTotal = $sale->items->sum(fn ($item) => (float) ($item->customer_discount_amount ?? $item->discount_amount ?? 0));
                    $historySalePictureUrls = $sale->salePictureUrls();
                    $historyPaymentProofUrls = $sale->paymentProofUrls();
                    $historyThumbUrls = array_slice(array_values(array_unique(array_merge($historySalePictureUrls, $historyPaymentProofUrls))), 0, 5);
                @endphp
                <article class="rounded-3xl bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#e7682b]">{{ $sale->sale_number }}</p>
                        <p class="max-w-[55%] truncate text-right text-[11px] text-slate-500">{{ $sale->businessSite->site_name }}</p>
                    </div>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $sale->sold_at->format('d M Y, h:i A') }}</p>
                    <div class="mt-2 overflow-x-auto">
                        <div class="flex min-w-max items-center gap-4 text-xs font-semibold whitespace-nowrap">
                            <span class="text-slate-500">Harga asal <strong class="ml-1 text-slate-700">RM {{ number_format($grossTotal, 2) }}</strong></span>
                            <span class="text-slate-500">Discount <strong class="ml-1 text-[#d95419]">- RM {{ number_format($discountTotal, 2) }}</strong></span>
                            <span class="text-slate-500">Net total <strong class="ml-1 text-[#17324d]">RM {{ number_format((float) $sale->total_amount, 2) }}</strong></span>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3 text-xs">
                        <p class="truncate text-slate-500">Sales person <span class="font-bold text-slate-700">{{ $sale->salesAgent->agt_name }}</span></p>
                        <p class="shrink-0 text-slate-500">Payment <span class="font-bold uppercase text-slate-700">{{ $sale->payment_method }}</span></p>
                    </div>
                    <div class="mt-2 space-y-1 text-xs text-slate-600">@foreach ($sale->items as $item)<div class="flex justify-between gap-3"><span class="truncate">{{ $item->product_name }} × {{ $item->quantity }}</span><span class="shrink-0 font-semibold">RM {{ number_format((float) $item->line_total, 2) }}</span></div>@endforeach</div>
                    @if (count($historyThumbUrls) > 0)
                        <div class="mt-2 flex items-center gap-2 overflow-x-auto pb-1">
                            @foreach ($historyThumbUrls as $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="block h-10 w-10 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                                    <img src="{{ $url }}" alt="Sale thumbnail" class="h-full w-full object-cover">
                                </a>
                            @endforeach
                        </div>
                    @endif
                    @if ($activeSession && $activeSession->business_site_id === $sale->business_site_id)
                        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                            <button type="button" data-open-pos-delete data-action="{{ route('agent.pos.sales.destroy', $sale) }}" data-sale-number="{{ $sale->sale_number }}" class="text-xs font-normal italic text-slate-400 transition hover:text-slate-600">Delete</button>
                            <a href="{{ route('agent.pos.sales.edit', $sale) }}" class="rounded-xl border border-orange-200 px-4 py-2 text-center text-xs font-extrabold text-[#d95419]">Edit sale</a>
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 p-8 text-center text-sm font-semibold text-slate-500">No POS sales recorded yet.</div>
            @endforelse
        </div>
        {{ $sales->withQueryString()->links() }}
    @endif

    <div class="fixed inset-0 z-[70] hidden items-end justify-center bg-slate-950/60 p-0 backdrop-blur-sm sm:items-center sm:p-5" data-pos-delete-modal data-open-on-load="{{ $errors->deleteSale->any() ? 'true' : 'false' }}" data-action="{{ old('delete_action') }}" data-sale-number="{{ old('delete_sale_number') }}" role="dialog" aria-modal="true" aria-labelledby="pos-delete-title">
        <button type="button" class="absolute inset-0" data-close-pos-delete aria-label="Close delete confirmation"></button>
        <div class="relative w-full max-w-md rounded-t-[2rem] bg-white p-5 shadow-2xl sm:rounded-[2rem]">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-red-600">Permanent action</p>
                    <h2 id="pos-delete-title" class="mt-1 text-lg font-extrabold text-[#17324d]">Delete this sale?</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Enter your agent password to confirm. This action cannot be undone.</p>
                </div>
                <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-600" data-close-pos-delete aria-label="Close delete confirmation">×</button>
            </div>

            <form method="POST" action="{{ old('delete_action') }}" class="mt-5" data-pos-delete-form>
                @csrf
                @method('DELETE')
                <input type="hidden" name="delete_action" value="{{ old('delete_action') }}" data-pos-delete-action>
                <input type="hidden" name="delete_sale_number" value="{{ old('delete_sale_number') }}" data-pos-delete-sale-number>

                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">Sale <strong class="text-[#17324d]" data-pos-delete-sale-label>{{ old('delete_sale_number') }}</strong></div>
                <label for="pos_delete_password" class="mt-4 block text-xs font-bold uppercase tracking-wider text-slate-500">Agent password</label>
                <input id="pos_delete_password" name="delete_password" type="password" autocomplete="current-password" required class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-red-400 focus:ring-2 focus:ring-red-100">
                @error('delete_password', 'deleteSale')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <button type="button" class="h-12 rounded-2xl border border-slate-200 text-sm font-extrabold text-slate-700" data-close-pos-delete>Cancel</button>
                    <button type="submit" class="h-12 rounded-2xl bg-red-600 text-sm font-extrabold text-white">Delete sale</button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('agent.pos._script')
@endsection
