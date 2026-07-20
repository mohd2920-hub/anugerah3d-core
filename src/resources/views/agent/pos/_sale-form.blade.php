@php
    $isEdit = isset($posSale);
    $currentItems = old('items', $isEdit
        ? $posSale->items->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity, 'discount_percentage' => $item->discount_percentage])->all()
        : [['product_id' => '', 'quantity' => 1]]);
    $inputClass = 'mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100';
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-5" data-pos-sale-form>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <section class="rounded-3xl bg-white p-5 shadow-sm">
        <h2 class="text-base font-extrabold text-[#17324d]">Sale details</h2>
        <label class="mt-4 block text-xs font-bold uppercase tracking-wider text-slate-500">Sales person</label>
        <select name="sales_agent_id" required class="{{ $inputClass }}" data-pos-sales-agent>
            <option value="">Choose agent</option>
            @foreach ($salesAgents as $salesAgent)
                <option value="{{ $salesAgent->id }}" data-discount="{{ $salesAgent->discount_percentage }}" @selected((string) old('sales_agent_id', $posSale->sales_agent_id ?? auth('agent')->id()) === (string) $salesAgent->id)>{{ $salesAgent->agt_name }} · {{ $salesAgent->login_id }}</option>
            @endforeach
        </select>
        @error('sales_agent_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror

        <div class="mt-5 flex items-center justify-between"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">Products</p><button type="button" data-add-pos-item class="rounded-full bg-orange-50 px-3 py-1.5 text-xs font-extrabold text-[#d95419]">+ Add product</button></div>
        <div class="mt-3 space-y-3" data-pos-items>
            @foreach ($currentItems as $index => $currentItem)
                <div class="rounded-2xl border border-slate-200 p-3" data-pos-item>
                    <div class="flex gap-2">
                        <select name="items[{{ $index }}][product_id]" required class="min-w-0 flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm" data-pos-product>
                            <option value="">Choose product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->price_selling }}" data-default-discount="{{ $product->agent_discount_default }}" @selected((string) $currentItem['product_id'] === (string) $product->id)>{{ $product->prd_name }} · RM {{ number_format((float) $product->price_selling, 2) }}</option>
                            @endforeach
                        </select>
                        <button type="button" data-remove-pos-item class="grid h-11 w-11 place-items-center rounded-xl bg-red-50 text-red-600" aria-label="Remove product">×</button>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <div><label class="text-[10px] font-bold uppercase text-slate-400">Quantity</label><input type="number" name="items[{{ $index }}][quantity]" value="{{ $currentItem['quantity'] }}" min="1" max="9999" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" data-pos-quantity></div>
                        <button type="button" class="rounded-xl bg-slate-50 px-3 py-2 text-left transition active:bg-orange-50" data-open-pos-discount>
                            <span class="block text-[10px] font-bold uppercase text-slate-400">Line total</span>
                            <span class="mt-1 block text-sm font-extrabold text-[#17324d]" data-pos-line-total>RM 0.00</span>
                            <span class="mt-1 block text-[10px] font-bold text-[#d95419]" data-pos-discount-label>Discount 0%</span>
                        </button>
                        <input type="hidden" name="items[{{ $index }}][discount_percentage]" value="{{ $currentItem['discount_percentage'] ?? '' }}" data-pos-discount data-custom-discount="{{ array_key_exists('discount_percentage', $currentItem) ? 'true' : 'false' }}">
                    </div>
                </div>
            @endforeach
        </div>
        @error('items')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        @error('items.*.product_id')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        @error('items.*.quantity')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        @error('items.*.discount_percentage')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        <div class="mt-4 rounded-2xl bg-[#17324d] px-4 py-4 text-white">
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
            <input id="sale_picture" type="file" name="sale_picture" accept="image/*" class="sr-only" data-pos-picture>
            <div class="mt-3 grid grid-cols-2 gap-2"><button type="button" data-picture-input="sale_picture" data-picture-mode="gallery" class="rounded-xl border border-slate-200 py-2.5 text-xs font-bold">Gallery</button><button type="button" data-picture-input="sale_picture" data-picture-mode="camera" class="rounded-xl bg-orange-50 py-2.5 text-xs font-bold text-[#d95419]">Take picture</button></div>
            <p class="mt-2 truncate text-xs text-slate-500" data-picture-name="sale_picture">{{ $isEdit && $posSale->sale_picture_path ? 'Current picture retained unless replaced' : 'No picture selected' }}</p>
        </div>
    </section>

    <section class="rounded-3xl bg-white p-5 shadow-sm">
        <h2 class="text-base font-extrabold text-[#17324d]">Payment</h2>
        <div class="mt-3 grid grid-cols-2 gap-3">
            @foreach ($paymentMethods as $value => $label)
                <label class="cursor-pointer"><input type="radio" name="payment_method" value="{{ $value }}" class="peer sr-only" @checked(old('payment_method', $posSale->payment_method ?? 'cash') === $value)><span class="flex h-14 items-center justify-center rounded-2xl border border-slate-200 text-sm font-extrabold text-slate-600 peer-checked:border-orange-400 peer-checked:bg-orange-50 peer-checked:text-[#d95419]">{{ $label }}</span></label>
            @endforeach
        </div>
        @error('payment_method')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        <label class="mt-4 block text-xs font-bold uppercase tracking-wider text-slate-500">Payment remark</label>
        <input name="payment_remark" value="{{ old('payment_remark', $posSale->payment_remark ?? '') }}" maxlength="500" class="{{ $inputClass }}">
        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Payment proof <span class="normal-case text-slate-400">(required for QR)</span></p>
            <input id="payment_proof" type="file" name="payment_proof" accept="image/*" class="sr-only" data-pos-picture>
            <div class="mt-3 grid grid-cols-2 gap-2"><button type="button" data-picture-input="payment_proof" data-picture-mode="gallery" class="rounded-xl border border-slate-200 py-2.5 text-xs font-bold">Gallery</button><button type="button" data-picture-input="payment_proof" data-picture-mode="camera" class="rounded-xl bg-orange-50 py-2.5 text-xs font-bold text-[#d95419]">Take picture</button></div>
            <p class="mt-2 truncate text-xs text-slate-500" data-picture-name="payment_proof">{{ $isEdit && $posSale->payment_proof_path ? 'Current proof retained unless replaced' : 'No proof selected' }}</p>
        </div>
        @error('payment_proof')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </section>

    <button class="h-14 w-full rounded-2xl bg-[#e7682b] text-sm font-extrabold text-white shadow-lg shadow-orange-500/20" data-pos-submit>{{ $submitLabel }}</button>
</form>

<template data-pos-item-template>
    <div class="rounded-2xl border border-slate-200 p-3" data-pos-item>
        <div class="flex gap-2"><select required class="min-w-0 flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm" data-pos-product><option value="">Choose product</option>@foreach ($products as $product)<option value="{{ $product->id }}" data-price="{{ $product->price_selling }}" data-default-discount="{{ $product->agent_discount_default }}">{{ $product->prd_name }} · RM {{ number_format((float) $product->price_selling, 2) }}</option>@endforeach</select><button type="button" data-remove-pos-item class="grid h-11 w-11 place-items-center rounded-xl bg-red-50 text-red-600">×</button></div>
        <div class="mt-2 grid grid-cols-2 gap-2"><div><label class="text-[10px] font-bold uppercase text-slate-400">Quantity</label><input type="number" value="1" min="1" max="9999" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" data-pos-quantity></div><button type="button" class="rounded-xl bg-slate-50 px-3 py-2 text-left transition active:bg-orange-50" data-open-pos-discount><span class="block text-[10px] font-bold uppercase text-slate-400">Line total</span><span class="mt-1 block text-sm font-extrabold text-[#17324d]" data-pos-line-total>RM 0.00</span><span class="mt-1 block text-[10px] font-bold text-[#d95419]" data-pos-discount-label>Discount 0%</span></button><input type="hidden" value="" data-pos-discount data-custom-discount="false"></div>
    </div>
</template>

<div class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/60 p-0 backdrop-blur-sm sm:items-center sm:p-5" data-pos-discount-modal role="dialog" aria-modal="true" aria-labelledby="pos-discount-title">
    <div class="w-full max-w-md rounded-t-[2rem] bg-white p-5 shadow-2xl sm:rounded-[2rem]">
        <div class="flex items-start justify-between gap-4">
            <div><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#e7682b]">Item discount</p><h2 id="pos-discount-title" class="mt-1 text-lg font-extrabold text-[#17324d]">Adjust line discount</h2></div>
            <button type="button" class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-600" data-close-pos-discount aria-label="Close discount modal">×</button>
        </div>
        <div class="mt-5 rounded-2xl bg-orange-50 p-4"><p class="text-xs font-bold uppercase tracking-wider text-orange-700">Salesperson baseline</p><p class="mt-1 text-lg font-extrabold text-orange-950" data-discount-baseline>0%</p></div>
        <label for="pos_discount_percentage" class="mt-5 block text-xs font-bold uppercase tracking-wider text-slate-500">Discount percentage</label>
        <div class="relative mt-2"><input id="pos_discount_percentage" type="number" min="0" max="100" step="0.01" inputmode="decimal" class="w-full rounded-2xl border border-slate-200 px-4 py-3 pr-12 text-lg font-extrabold text-[#17324d] outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100" data-discount-percentage><span class="pointer-events-none absolute right-4 top-3.5 font-bold text-slate-400">%</span></div>
        <dl class="mt-5 space-y-3 rounded-2xl bg-slate-50 p-4 text-sm"><div class="flex justify-between"><dt class="text-slate-500">Gross line total</dt><dd class="font-semibold text-slate-900" data-discount-gross>RM 0.00</dd></div><div class="flex justify-between"><dt class="text-slate-500">Discount amount</dt><dd class="font-semibold text-[#d95419]" data-discount-amount>- RM 0.00</dd></div><div class="flex justify-between border-t border-slate-200 pt-3"><dt class="font-bold text-slate-700">Net line total</dt><dd class="text-lg font-extrabold text-[#17324d]" data-discount-net>RM 0.00</dd></div></dl>
        <div class="mt-5 grid grid-cols-2 gap-3"><button type="button" class="h-12 rounded-2xl border border-slate-200 text-sm font-extrabold text-slate-700" data-reset-pos-discount>Use baseline</button><button type="button" class="h-12 rounded-2xl bg-[#e7682b] text-sm font-extrabold text-white" data-apply-pos-discount>Apply discount</button></div>
    </div>
</div>
