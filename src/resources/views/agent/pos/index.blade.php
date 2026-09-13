@extends('agent.layouts.app')

@section('title', 'POS | Anugerah3D Agent')
@section('page_title', 'Point of Sale')

@section('content')
<div class="space-y-5" data-pos-root>
    @if (session('success'))<div class="rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-700">{{ session('error') }}</div>@endif

    @if ($activeSession)
        <section class="overflow-hidden rounded-3xl bg-[#17324d] p-5 text-white shadow-xl">
            <div class="flex items-start justify-between gap-4"><div><p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">Checked in at</p><h2 class="mt-1 text-xl font-extrabold">{{ $activeSession->businessSite->site_name }}</h2><p class="text-sm text-slate-300">{{ $activeSession->businessSite->city }}</p></div><form method="POST" action="{{ route('agent.pos.sign-out') }}">@csrf<button class="rounded-full border border-white/20 px-3 py-2 text-xs font-bold">Check out</button></form></div>
            <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-white/10 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-300">Checked in for</p>
                    <p class="mt-1 font-mono text-2xl font-black" data-pos-timer data-signed-in-at="{{ $activeSession->signed_in_at->toIso8601String() }}">00:00:00</p>
                </div>
                <div class="rounded-2xl bg-white/10 p-4" data-operation-sales-summary>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-300">Operation sales</p>
                    <p class="mt-1 text-2xl font-black">RM {{ number_format($operationSales['total'], 2) }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-300">{{ number_format($operationSales['count']) }} sale(s) | Session #{{ $activeOperation?->getKey() ?? '-' }}</p>
                </div>
            </div>
        </section>
    @else
        <section class="rounded-3xl bg-white p-5 shadow-sm">
            <div class="grid h-14 w-14 place-items-center rounded-2xl bg-orange-100 text-[#d95419]"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18v12H3z"/><path d="M7 12h.01M17 12h.01"/><circle cx="12" cy="12" r="2"/></svg></div>
            <h2 class="mt-4 text-xl font-extrabold text-[#17324d]">Check in to POS</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Choose your assigned business site. Your session remains active until you check out.</p>
            @if ($businessSites->isNotEmpty())
                <form method="POST" action="{{ route('agent.pos.sign-in') }}" class="mt-5 space-y-3" data-pos-checkin-form>
                    @csrf
                    <select name="business_site_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" data-pos-site-select>
                        <option value="">Choose business site</option>
                        @foreach ($businessSites as $site)
                            <option value="{{ $site->id }}" data-site-open="{{ $site->isOpen() ? 'true' : 'false' }}" @selected((string) old('business_site_id') === (string) $site->id)>{{ $site->site_name }} · {{ $site->city }} · {{ $site->isOpen() ? 'Open' : 'Closed' }}</option>
                        @endforeach
                    </select>
                    @error('business_site_id')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    <button class="h-12 w-full rounded-2xl bg-[#e7682b] text-sm font-extrabold text-white">Check in</button>
                </form>
            @else
                <div class="mt-5 rounded-2xl bg-amber-50 p-4 text-sm font-semibold text-amber-800">No business site has been assigned to your account. Please contact admin.</div>
            @endif
        </section>
    @endif

    <div class="fixed inset-0 z-[70] hidden items-end justify-center bg-slate-950/60 p-0 backdrop-blur-sm sm:items-center sm:p-5" data-site-closed-modal data-open-on-load="{{ $errors->has('business_site_id') && old('business_site_id') ? 'true' : 'false' }}" role="dialog" aria-modal="true" aria-labelledby="site-closed-title">
        <button type="button" class="absolute inset-0" data-close-site-closed aria-label="Close business site notice"></button>
        <div class="relative w-full max-w-md rounded-t-[2rem] bg-white p-6 text-center shadow-2xl sm:rounded-[2rem]">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-amber-100 text-2xl font-black text-amber-700">!</div>
            <h2 id="site-closed-title" class="mt-4 text-xl font-extrabold text-[#17324d]">Business site is not open</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Please ask an admin to open the business site before you check in.</p>
            <button type="button" class="mt-5 h-12 w-full rounded-2xl bg-[#17324d] text-sm font-extrabold text-white" data-close-site-closed>Okay</button>
        </div>
    </div>

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
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#e7682b]">{{ $sale->sale_number }} @if ($sale->voided_at)<span class="rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Void</span>@endif @if ($sale->correction_version > 0)<span class="rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Corrected</span>@endif</p>
                        <p class="max-w-[55%] truncate text-right text-[11px] text-slate-500">{{ $sale->businessSite->site_name }}</p>
                    </div>
                    <div class="mt-0.5 flex items-center justify-between gap-3 text-xs text-slate-500">
                        <p>{{ $sale->sold_at->format('d M Y, h:i A') }}</p>
                        <p class="shrink-0 font-semibold">Session #{{ $sale->business_site_operation_id }}</p>
                    </div>
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
                    <div class="mt-2 space-y-1 text-xs text-slate-600">@foreach ($sale->items as $item)<div class="flex justify-between gap-3"><span class="truncate">{{ $item->product_name }} × {{ $item->quantity }}<x-clicker-sale-details :configuration="$item->clicker_configuration ?? null" /></span><span class="shrink-0 font-semibold">RM {{ number_format((float) $item->line_total, 2) }}</span></div>@endforeach</div>
                    @if (count($historyThumbUrls) > 0)
                        <div class="mt-2 flex items-center gap-2 overflow-x-auto pb-1">
                            @foreach ($historyThumbUrls as $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="block h-10 w-10 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                                    <img src="{{ $url }}" alt="Sale thumbnail" class="h-full w-full object-cover">
                                </a>
                            @endforeach
                        </div>
                    @endif
                    @if (! $sale->voided_at && $sale->customer_email && $activeOperation && $activeOperation->getKey() === $sale->business_site_operation_id)
                        <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                            <div class="flex items-center gap-2">
                                @if ($sale->customer_email)
                                    <form method="POST" action="{{ route('agent.pos.sales.receipt', $sale) }}">@csrf<button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-center text-xs font-extrabold text-[#17324d]">Send receipt</button></form>
                                @else
                                    <button type="button" data-open-pos-receipt data-action="{{ route('agent.pos.sales.receipt', $sale) }}" data-sale-number="{{ $sale->sale_number }}" data-customer-name="{{ $sale->customer_name }}" class="rounded-xl border border-slate-200 px-3 py-2 text-center text-xs font-extrabold text-[#17324d]">Send receipt</button>
                                @endif
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 p-8 text-center text-sm font-semibold text-slate-500">No POS sales recorded yet.</div>
            @endforelse
        </div>
        {{ $sales->withQueryString()->links() }}
    @endif

</div>
@include('agent.pos._script')
@endsection
