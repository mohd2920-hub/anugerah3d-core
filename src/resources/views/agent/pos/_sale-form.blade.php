@php
    $isEdit = isset($posSale);
    $selectedSalesAgentId = (string) old('sales_agent_id', $posSale->sales_agent_id ?? auth('agent')->id());
    $currentSalePictures = $isEdit ? $posSale->salePicturePaths() : [];
    $currentPaymentProofs = $isEdit ? $posSale->paymentProofPaths() : [];
    $currentSalePictureUrls = $isEdit ? $posSale->salePictureUrls() : [];
    $currentPaymentProofUrls = $isEdit ? $posSale->paymentProofUrls() : [];
    $currentItems = old('items', $isEdit
        ? $posSale->items->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity, 'discount_amount' => $item->customer_discount_amount ?? $item->discount_amount ?? 0])->all()
        : [['product_id' => '', 'quantity' => 1]]);
    $productPictureUrl = function ($product): ?string {
        $path = $product->images->first()?->image_path ?: $product->prd_picture;

        if (! $path) {
            return null;
        }

        return filter_var($path, FILTER_VALIDATE_URL) ? $path : asset(ltrim($path, '/'));
    };
    $inputClass = 'mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100';
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-5" data-pos-sale-form>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <section class="rounded-3xl bg-white p-5 shadow-sm">
        <h2 class="text-base font-extrabold text-[#17324d]">Sale details</h2>
        <label class="mt-4 block text-xs font-bold uppercase tracking-wider text-slate-500">Sales person</label>
        <input type="hidden" name="sales_agent_id" value="{{ $selectedSalesAgentId }}" data-pos-sales-agent-input>
        <div class="mt-2 overflow-x-auto pb-1">
            <div class="grid min-w-[540px] grid-cols-5 gap-2" data-pos-sales-agent-grid>
            @foreach ($salesAgents as $salesAgent)
                @php
                    $agentId = (string) $salesAgent->id;
                    $isSelectedAgent = $selectedSalesAgentId === $agentId;
                    $initials = collect(preg_split('/\s+/', trim($salesAgent->agt_name)) ?: [])
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                        ->join('');
                    $displayName = mb_strlen($salesAgent->agt_name) > 10
                        ? mb_substr($salesAgent->agt_name, 0, 10).'...'
                        : $salesAgent->agt_name;
                @endphp
                <button type="button" data-pos-sales-agent-choice data-agent-id="{{ $agentId }}" @class([
                    'min-w-0 rounded-xl border px-1.5 py-2 text-center transition',
                    'border-orange-300 bg-orange-50 text-[#d95419]' => $isSelectedAgent,
                    'border-slate-200 bg-white text-slate-700 active:bg-slate-50' => ! $isSelectedAgent,
                ]) title="{{ $salesAgent->agt_name }} · {{ $salesAgent->login_id }}">
                    @if ($salesAgent->profile_picture)
                        <span data-pos-agent-photo class="mx-auto block h-8 w-8 overflow-hidden rounded-full ring-1" @class([
                            'ring-orange-300' => $isSelectedAgent,
                            'ring-slate-200' => ! $isSelectedAgent,
                        ])>
                            <img src="{{ filter_var($salesAgent->profile_picture, FILTER_VALIDATE_URL) ? $salesAgent->profile_picture : asset($salesAgent->profile_picture) }}" alt="" class="h-full w-full object-cover">
                        </span>
                    @else
                        <span data-pos-agent-icon @class([
                            'mx-auto grid h-8 w-8 place-items-center rounded-full text-[11px] font-black',
                            'bg-[#d95419] text-white' => $isSelectedAgent,
                            'bg-slate-100 text-slate-700' => ! $isSelectedAgent,
                        ])>{{ $initials !== '' ? $initials : 'AG' }}</span>
                    @endif
                    <span class="mt-1 block truncate text-[10px] font-semibold leading-tight">{{ $displayName }}</span>
                </button>
            @endforeach
            </div>
        </div>
        @error('sales_agent_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror

        <div class="mt-5 flex items-center justify-between"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">Products</p><button type="button" data-add-pos-item class="rounded-full bg-orange-50 px-3 py-1.5 text-xs font-extrabold text-[#d95419]">+ Add product</button></div>
        @if ($topProducts->isNotEmpty())
            <div class="mt-3">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Top selling at this site</p>
                    <p class="text-[10px] font-semibold text-slate-400">Tap to add</p>
                </div>
                <div class="mt-2 grid gap-1.5" style="grid-template-columns: repeat(7, minmax(0, 1fr));" data-pos-quick-products>
                    @foreach ($topProducts as $product)
                        @php
                            $pictureUrl = $productPictureUrl($product);
                            $productLabel = $product->prd_code.' · '.$product->prd_name.' · RM '.number_format((float) $product->price_selling, 2);
                        @endphp
                        <button type="button" data-pos-quick-product data-value="{{ $product->id }}" data-label="{{ $productLabel }}" data-name="{{ $product->prd_name }}" class="relative min-w-0 rounded-xl border border-slate-200 bg-white p-1 transition active:scale-95 active:border-orange-300 active:bg-orange-50" style="aspect-ratio: 1 / 1;" title="{{ $product->prd_code }} · {{ $product->prd_name }}" aria-label="Add {{ $product->prd_name }}">
                            @if ($pictureUrl)
                                <img src="{{ $pictureUrl }}" alt="" loading="lazy" class="h-full w-full rounded-lg bg-slate-100 object-cover">
                            @else
                                <span class="grid h-full w-full place-items-center rounded-lg bg-slate-100 text-slate-400" aria-hidden="true">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m4 16 4-4 4 4 3-3 5 5"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="mt-3 space-y-3" data-pos-items>
            @foreach ($currentItems as $index => $currentItem)
                <div class="rounded-2xl border border-slate-200 p-3" data-pos-item>
                    @php
                        $selectedProduct = $products->firstWhere('id', $currentItem['product_id'] ?? null);
                        $selectedProductLabel = $selectedProduct
                            ? $selectedProduct->prd_code.' · '.$selectedProduct->prd_name.' · RM '.number_format((float) $selectedProduct->price_selling, 2)
                            : '';
                    @endphp
                    <div class="flex gap-2">
                        <div class="relative min-w-0 flex-1">
                            <input type="search" value="{{ $selectedProductLabel }}" placeholder="Choose product" autocomplete="off" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" data-pos-product-search>
                            <select name="items[{{ $index }}][product_id]" class="sr-only" data-pos-product>
                                <option value="">Choose product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ $product->price_selling }}" data-default-discount="{{ $product->agent_discount_default }}" data-code="{{ $product->prd_code }}" data-name="{{ $product->prd_name }}" data-search="{{ Str::lower($product->prd_code.' '.$product->prd_name) }}" data-image="{{ $productPictureUrl($product) }}" @selected((string) $currentItem['product_id'] === (string) $product->id)>{{ $product->prd_code }} · {{ $product->prd_name }} · RM {{ number_format((float) $product->price_selling, 2) }}</option>
                                @endforeach
                            </select>
                            <div class="absolute left-0 right-0 top-full z-20 mt-1 hidden max-h-56 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg" data-pos-product-list></div>
                        </div>
                        <button type="button" data-remove-pos-item class="grid h-11 w-11 place-items-center rounded-xl bg-red-50 text-red-600" aria-label="Remove product">×</button>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-bold uppercase text-slate-400">Quantity</label>
                            <div class="mt-1 flex items-center gap-2">
                                <button type="button" data-pos-qty-minus class="grid h-11 w-11 place-items-center rounded-xl border border-slate-200 bg-slate-50 text-lg font-black text-[#17324d]">-</button>
                                <input type="number" name="items[{{ $index }}][quantity]" value="{{ $currentItem['quantity'] }}" min="1" max="9999" required class="h-11 min-w-0 flex-1 rounded-xl border border-slate-200 px-3 text-center text-base font-bold" data-pos-quantity>
                                <button type="button" data-pos-qty-plus class="grid h-11 w-11 place-items-center rounded-xl border border-slate-200 bg-slate-50 text-lg font-black text-[#17324d]">+</button>
                            </div>
                        </div>
                        <button type="button" class="rounded-xl bg-slate-50 px-3 py-2 text-left transition active:bg-orange-50" data-open-pos-discount>
                            <span class="block text-[10px] font-bold uppercase text-slate-400">Line total</span>
                            <span class="mt-1 block text-sm font-extrabold text-[#17324d]" data-pos-line-total>RM 0.00</span>
                            <span class="mt-1 block text-[10px] font-bold text-[#d95419]" data-pos-discount-label>Discount RM 0.00</span>
                        </button>
                        <input type="hidden" name="items[{{ $index }}][discount_amount]" value="{{ $currentItem['discount_amount'] ?? '' }}" data-pos-discount data-custom-discount="{{ array_key_exists('discount_amount', $currentItem) ? 'true' : 'false' }}">
                    </div>
                </div>
            @endforeach
        </div>
        @error('items')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        @error('items.*.product_id')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        @error('items.*.quantity')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        @error('items.*.discount_amount')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        <div class="mt-4 rounded-2xl bg-[#17324d] px-4 py-4 text-white" data-pos-summary-card>
            <div class="flex items-center justify-between text-xs text-slate-300"><span>Gross total</span><span data-pos-gross-total>RM 0.00</span></div>
            <div class="mt-2 flex items-center justify-between text-xs text-orange-300"><span>Total discount</span><strong data-pos-total-discount>- RM 0.00</strong></div>
            <div class="mt-3 flex items-center justify-between border-t border-white/15 pt-3"><span class="text-sm font-bold">Total sale</span><strong class="text-xl" data-pos-grand-total>RM 0.00</strong></div>
        </div>
    </section>

    <section class="rounded-3xl bg-white p-5 shadow-sm">
        <h2 class="text-base font-extrabold text-[#17324d]">Customer & remark</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div><label class="text-xs font-bold uppercase tracking-wider text-slate-500">Customer name <span class="normal-case text-slate-400">(optional)</span></label><input name="customer_name" value="{{ old('customer_name', $posSale->customer_name ?? '') }}" maxlength="150" class="{{ $inputClass }}"></div>
            <div><label class="text-xs font-bold uppercase tracking-wider text-slate-500">Phone <span class="normal-case text-slate-400">(optional)</span></label><input name="customer_phone" value="{{ old('customer_phone', $posSale->customer_phone ?? '') }}" maxlength="50" inputmode="tel" class="{{ $inputClass }}"></div>
        </div>
        <label class="mt-3 block text-xs font-bold uppercase tracking-wider text-slate-500">Remark</label>
        <textarea name="remark" maxlength="2000" rows="3" class="{{ $inputClass }}">{{ old('remark', $posSale->remark ?? '') }}</textarea>

        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Sale picture <span class="normal-case text-slate-400">(optional)</span></p>
            <input id="sale_pictures" type="file" name="sale_pictures[]" accept="image/*" multiple class="mt-3 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" data-pos-picture data-max-files="5">
            <p class="mt-2 truncate text-xs text-slate-500" data-picture-name="sale_pictures">{{ count($currentSalePictures) > 0 ? count($currentSalePictures).' current picture(s) retained unless replaced' : 'No picture selected' }}</p>
            @if (count($currentSalePictures) > 0)
                <div class="mt-2 flex gap-2 overflow-x-auto pb-1">
                    @foreach ($currentSalePictureUrls as $url)
                        <button type="button" data-pos-image-thumb data-preview-src="{{ $url }}" class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="{{ $url }}" alt="Current sale picture" class="h-full w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="rounded-3xl bg-white p-5 shadow-sm">
        <h2 class="text-base font-extrabold text-[#17324d]">Payment</h2>
        @php($selectedPaymentMethod = old('payment_method', $posSale->payment_method ?? 'cash'))
        <div class="mt-3 grid grid-cols-2 gap-3">
            @foreach ($paymentMethods as $value => $label)
                <label class="cursor-pointer"><input type="radio" name="payment_method" value="{{ $value }}" class="peer sr-only" @checked($selectedPaymentMethod === $value)><span class="flex h-14 items-center justify-center rounded-2xl border border-slate-200 text-sm font-extrabold text-slate-600 peer-checked:border-orange-400 peer-checked:bg-orange-50 peer-checked:text-[#d95419]">{{ $label }}</span></label>
            @endforeach
        </div>
        @error('payment_method')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        <label class="mt-4 block text-xs font-bold uppercase tracking-wider text-slate-500">Payment remark</label>
        <input name="payment_remark" value="{{ old('payment_remark', $posSale->payment_remark ?? '') }}" maxlength="500" class="{{ $inputClass }}">
        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Payment proof <span class="normal-case text-slate-400">(required for QR)</span></p>
            <input id="payment_proofs" type="file" name="payment_proofs[]" accept="image/*" multiple class="mt-3 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" data-pos-picture data-max-files="5">
            <p class="mt-2 truncate text-xs text-slate-500" data-picture-name="payment_proofs">{{ count($currentPaymentProofs) > 0 ? count($currentPaymentProofs).' current proof(s) retained unless replaced' : 'No proof selected' }}</p>
            @if (count($currentPaymentProofs) > 0)
                <div class="mt-2 flex gap-2 overflow-x-auto pb-1">
                    @foreach ($currentPaymentProofUrls as $url)
                        <button type="button" data-pos-image-thumb data-preview-src="{{ $url }}" class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="{{ $url }}" alt="Current payment proof" class="h-full w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
        @error('sale_pictures')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        @error('sale_pictures.*')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        @error('payment_proofs')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        @error('payment_proofs.*')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </section>

    <div class="h-28"></div>
    <div class="fixed inset-x-0 z-50 hidden" style="bottom: calc(84px + env(safe-area-inset-bottom));" data-pos-submit-wrap>
        <div class="mx-auto w-full max-w-xl px-4 sm:px-5">
            <button class="h-14 w-full rounded-2xl bg-[#e7682b] text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-600 disabled:shadow-none" data-pos-submit>{{ $submitLabel }}</button>
        </div>
    </div>

</form>

<template data-pos-item-template>
    <div class="rounded-2xl border border-slate-200 p-3" data-pos-item>
        <div class="flex gap-2"><div class="relative min-w-0 flex-1"><input type="search" value="" placeholder="Choose product" autocomplete="off" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" data-pos-product-search><select class="sr-only" data-pos-product><option value="">Choose product</option>@foreach ($products as $product)<option value="{{ $product->id }}" data-price="{{ $product->price_selling }}" data-default-discount="{{ $product->agent_discount_default }}" data-code="{{ $product->prd_code }}" data-name="{{ $product->prd_name }}" data-search="{{ Str::lower($product->prd_code.' '.$product->prd_name) }}" data-image="{{ $productPictureUrl($product) }}">{{ $product->prd_code }} · {{ $product->prd_name }} · RM {{ number_format((float) $product->price_selling, 2) }}</option>@endforeach</select><div class="absolute left-0 right-0 top-full z-20 mt-1 hidden max-h-56 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg" data-pos-product-list></div></div><button type="button" data-remove-pos-item class="grid h-11 w-11 place-items-center rounded-xl bg-red-50 text-red-600">×</button></div>
        <div class="mt-2 grid grid-cols-2 gap-2"><div><label class="text-[10px] font-bold uppercase text-slate-400">Quantity</label><div class="mt-1 flex items-center gap-2"><button type="button" data-pos-qty-minus class="grid h-11 w-11 place-items-center rounded-xl border border-slate-200 bg-slate-50 text-lg font-black text-[#17324d]">-</button><input type="number" value="1" min="1" max="9999" required class="h-11 min-w-0 flex-1 rounded-xl border border-slate-200 px-3 text-center text-base font-bold" data-pos-quantity><button type="button" data-pos-qty-plus class="grid h-11 w-11 place-items-center rounded-xl border border-slate-200 bg-slate-50 text-lg font-black text-[#17324d]">+</button></div></div><button type="button" class="rounded-xl bg-slate-50 px-3 py-2 text-left transition active:bg-orange-50" data-open-pos-discount><span class="block text-[10px] font-bold uppercase text-slate-400">Line total</span><span class="mt-1 block text-sm font-extrabold text-[#17324d]" data-pos-line-total>RM 0.00</span><span class="mt-1 block text-[10px] font-bold text-[#d95419]" data-pos-discount-label>Discount RM 0.00</span></button><input type="hidden" value="" data-pos-discount data-custom-discount="false"></div>
    </div>
</template>

<div class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/60 p-0 backdrop-blur-sm sm:items-center sm:p-5" data-pos-discount-modal role="dialog" aria-modal="true" aria-labelledby="pos-discount-title">
    <div class="w-full max-w-md rounded-t-[2rem] bg-white p-5 shadow-2xl sm:rounded-[2rem]">
        <div class="flex items-start justify-between gap-4">
            <div><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#e7682b]">Item discount</p><h2 id="pos-discount-title" class="mt-1 text-lg font-extrabold text-[#17324d]">Adjust line discount</h2></div>
            <button type="button" class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-600" data-close-pos-discount aria-label="Close discount modal">×</button>
        </div>
        <label for="pos_discount_amount" class="mt-5 block text-xs font-bold uppercase tracking-wider text-slate-500">Discount amount (RM)</label>
        <div class="mt-2 flex items-stretch overflow-hidden rounded-2xl border border-slate-200 focus-within:border-orange-400 focus-within:ring-2 focus-within:ring-orange-100"><span class="grid w-14 place-items-center bg-slate-50 text-sm font-bold text-slate-500">RM</span><input id="pos_discount_amount" type="number" min="0" step="0.01" inputmode="decimal" class="w-full border-0 px-4 py-3 text-lg font-extrabold text-[#17324d] outline-none focus:ring-0" data-discount-value-input></div>
        <dl class="mt-5 space-y-3 rounded-2xl bg-slate-50 p-4 text-sm"><div class="flex justify-between"><dt class="text-slate-500">Gross total</dt><dd class="font-semibold text-slate-900" data-discount-gross>RM 0.00</dd></div><div class="flex justify-between"><dt class="text-slate-500">Discount amount</dt><dd class="font-semibold text-[#d95419]" data-discount-preview-amount>- RM 0.00</dd></div><div class="flex justify-between border-t border-slate-200 pt-3"><dt class="font-bold text-slate-700">Net line total</dt><dd class="text-lg font-extrabold text-[#17324d]" data-discount-net>RM 0.00</dd></div></dl>
        <div class="mt-5 grid grid-cols-2 gap-3"><button type="button" class="h-12 rounded-2xl border border-slate-200 text-sm font-extrabold text-slate-700" data-reset-pos-discount>Reset to RM 0.00</button><button type="button" class="h-12 rounded-2xl bg-[#e7682b] text-sm font-extrabold text-white" data-apply-pos-discount>Apply discount</button></div>
    </div>
</div>

<div class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/80 p-4" data-pos-image-modal>
    <button type="button" class="absolute inset-0" data-pos-image-close aria-label="Close image preview"></button>
    <div class="relative w-full max-w-3xl">
        <button type="button" class="absolute -top-12 right-0 grid h-10 w-10 place-items-center rounded-full bg-white/20 text-2xl leading-none text-white" data-pos-image-close aria-label="Close image preview">×</button>
        <div class="overflow-hidden rounded-2xl bg-white shadow-2xl">
            <img src="" alt="Preview image" class="max-h-[78vh] w-full bg-slate-100 object-contain" data-pos-image-modal-src>
        </div>
    </div>
</div>
