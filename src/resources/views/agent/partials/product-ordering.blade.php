<section class="space-y-4">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#e7682b]">Create an order</p>
        <h2 class="mt-1 text-lg font-extrabold text-[#17324d]">Products</h2>
        <p class="mt-1 text-xs leading-5 text-slate-500">Available and pre-order products are shown below. Use search to filter the list.</p>
    </div>

    <div class="sticky top-[72px] z-20 -mx-1 space-y-3 rounded-3xl bg-[#f7f9fa]/95 px-1 py-2 backdrop-blur">
        <label class="relative block">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input data-product-search type="search" autocomplete="off" placeholder="Search product name or code" class="h-12 w-full rounded-2xl border border-slate-200 bg-white pl-12 pr-4 text-sm outline-none shadow-sm transition focus:border-[#e7682b] focus:ring-4 focus:ring-orange-100">
        </label>

        <div data-cart-visible class="hidden items-center gap-2 rounded-2xl border border-orange-100 bg-white p-2 shadow-sm">
            <button type="button" data-open-cart class="relative grid h-11 w-11 flex-none place-items-center rounded-xl bg-orange-50 text-[#e7682b]" aria-label="View shopping cart">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M3 4h2l2.5 11h10.8l2-7H7"/></svg>
                <span data-cart-units class="absolute -right-1.5 -top-1.5 grid min-h-5 min-w-5 place-items-center rounded-full bg-[#e7682b] px-1 text-[9px] font-black text-white">0</span>
            </button>
            <div class="min-w-0 flex-1"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Cart total</p><p data-cart-total class="truncate text-sm font-black text-[#17324d]">RM 0.00</p></div>
            <button type="button" data-open-cart class="h-11 rounded-xl bg-[#e7682b] px-4 text-xs font-extrabold text-white shadow-sm shadow-orange-600/20">Checkout</button>
        </div>
    </div>

    <div data-product-results class="space-y-3">
        <div class="flex items-center justify-between"><p class="text-sm font-extrabold text-[#17324d]"><span data-result-count>{{ $products->count() }}</span> products</p><button type="button" data-clear-search class="hidden text-xs font-bold text-[#e7682b]">Clear</button></div>
        <div class="grid grid-cols-2 gap-3">
            @foreach ($products as $product)
                @php
                    $legacyPicture = $product->prd_picture ? (filter_var($product->prd_picture, FILTER_VALIDATE_URL) ? $product->prd_picture : asset($product->prd_picture)) : null;
                    $galleryImages = $product->images
                        ->take(5)
                        ->map(fn ($image) => [
                            'src' => filter_var($image->image_path, FILTER_VALIDATE_URL) ? $image->image_path : asset(ltrim($image->image_path, '/')),
                            'alt' => $image->alt_text ?: $product->prd_name,
                        ])
                        ->values();
                    if ($galleryImages->isEmpty() && $legacyPicture) {
                        $galleryImages->push(['src' => $legacyPicture, 'alt' => $product->prd_name]);
                    }
                    $picture = data_get($galleryImages->first(), 'src');
                    $price = (float) $product->price_selling;
                    $searchValue = Str::lower($product->prd_code.' '.$product->prd_name);
                    $isPreOrder = (int) $product->prd_balance <= 0;
                @endphp
                <article data-product-card data-product-id="{{ $product->getKey() }}" data-search-value="{{ $searchValue }}" role="button" tabindex="0" aria-label="View details for {{ $product->prd_name }}" class="flex min-w-0 cursor-pointer flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition active:scale-[0.99] focus:outline-none focus:ring-4 focus:ring-orange-100">
                    <div class="relative aspect-square overflow-hidden bg-slate-100">
                        @if ($picture)<img src="{{ $picture }}" alt="{{ $product->prd_name }}" loading="lazy" class="h-full w-full object-cover">@else<div class="grid h-full place-items-center bg-[linear-gradient(145deg,#f1f5f9,#e8eef5)] text-slate-300"><svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m4 16 4-4 4 4 3-3 5 5"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg></div>@endif
                        @if ($isPreOrder)
                            <div class="absolute inset-0 z-10 grid place-items-center bg-slate-950/50 p-3 text-center backdrop-blur-[1px]"><span class="rounded-full border border-white/20 bg-slate-950/70 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wider text-white shadow-lg">Out of stock</span></div>
                        @else
                            <span class="absolute left-2 top-2 rounded-full bg-emerald-50 px-2 py-1 text-[8px] font-extrabold uppercase text-emerald-700 shadow-sm">In stock</span>
                        @endif
                        <span data-card-cart-badge class="absolute right-2 top-2 z-20 hidden items-center gap-1 rounded-full bg-[#e7682b] px-2 py-1 text-[9px] font-black text-white shadow-lg"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M3 4h2l2.5 11h10.8l2-7H7"/></svg><span data-card-cart-quantity>0</span></span>
                    </div>
                    <div class="flex flex-1 flex-col p-3">
                        <p class="truncate text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ $product->prd_code }}</p>
                        <h3 class="mt-1 line-clamp-2 min-h-10 text-sm font-extrabold leading-5 text-[#17324d]">{{ $product->prd_name }}</h3>
                        <div class="mt-3 flex items-end justify-between gap-1"><span class="text-[9px] font-semibold text-slate-500">Price</span><span class="text-base font-black text-[#e7682b]">RM {{ number_format($price, 2) }}</span></div>
                        <button type="button" data-add-product data-id="{{ $product->getKey() }}" data-code="{{ $product->prd_code }}" data-name="{{ $product->prd_name }}" data-images="{{ $galleryImages->toJson() }}" data-price="{{ number_format($price, 2, '.', '') }}" data-max="{{ $isPreOrder ? 9999 : (int) $product->prd_balance }}" data-preorder="{{ $isPreOrder ? '1' : '0' }}" data-material="{{ $product->materialType?->name ?? $product->material ?? '' }}" data-color="{{ $product->color }}" data-weight="{{ $product->weight_g }}" data-width="{{ $product->width_mm }}" data-height="{{ $product->height_mm }}" data-length="{{ $product->length_mm }}" data-product-type="{{ $product->product_type ?? 'standard' }}" data-clicker-prices='@json(data_get($clickerCharacterPricesByProduct, (string) $product->getKey(), []))' data-clicker-casing-images='@json(data_get($clickerImagesByProduct, $product->getKey().".casing", []))' data-clicker-huruf-images='@json(data_get($clickerImagesByProduct, $product->getKey().".huruf", []))' @class(['mt-3 h-10 w-full rounded-xl text-xs font-extrabold text-white transition active:scale-[0.98]', 'bg-[#17324d]' => ! $isPreOrder, 'bg-[#e7682b]' => $isPreOrder])><span data-add-label>{{ $isPreOrder ? 'Pre-order' : 'Add' }}</span></button>
                    </div>
                </article>
            @endforeach
        </div>
        <div data-no-results class="hidden rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">No available products match your search.</div>
    </div>

    <button type="button" data-open-cart data-cart-visible class="hidden h-13 w-full items-center justify-center gap-2 rounded-2xl bg-[#e7682b] text-sm font-extrabold text-white shadow-lg shadow-orange-600/20"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M3 4h2l2.5 11h10.8l2-7H7"/></svg>Checkout · <span data-cart-units>0</span> units · <span data-cart-total>RM 0.00</span></button>
</section>

<div data-quantity-modal class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/45 backdrop-blur-sm sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="product-detail-title">
    <div class="flex max-h-[calc(100dvh-0.5rem)] w-full max-w-lg flex-col overflow-hidden rounded-t-[2rem] bg-white shadow-2xl sm:max-h-[88vh] sm:rounded-[2rem]">
        <div class="relative flex h-12 flex-none items-center justify-center px-4">
            <span class="h-1.5 w-12 rounded-full bg-slate-200"></span>
            <button type="button" data-close-quantity class="absolute right-3 grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-600 transition active:bg-slate-200" aria-label="Close product details"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
            <div class="relative mx-4 h-[clamp(9rem,28dvh,14rem)] overflow-hidden rounded-2xl bg-slate-100 sm:mx-5 sm:h-auto sm:aspect-[16/10] sm:rounded-3xl">
                <div data-product-gallery class="flex h-full w-full snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none]" aria-label="Product pictures"></div>
                <div data-product-gallery-placeholder class="hidden h-full place-items-center bg-[linear-gradient(145deg,#f1f5f9,#e8eef5)] text-slate-300"><svg class="h-14 w-14 sm:h-16 sm:w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m4 16 4-4 4 4 3-3 5 5"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg></div>
                <span data-modal-status class="absolute left-2.5 top-2.5 rounded-full px-2.5 py-1 text-[9px] font-extrabold uppercase shadow-sm sm:left-3 sm:top-3 sm:px-3 sm:py-1.5 sm:text-[10px]"></span>
                <button type="button" data-gallery-previous class="absolute left-2 top-1/2 hidden h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-[#17324d] shadow-md sm:left-3 sm:h-10 sm:w-10" aria-label="Previous picture"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg></button>
                <button type="button" data-gallery-next class="absolute right-2 top-1/2 hidden h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-[#17324d] shadow-md sm:right-3 sm:h-10 sm:w-10" aria-label="Next picture"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg></button>
                <span data-gallery-counter class="absolute bottom-2 right-2 hidden rounded-full bg-slate-950/65 px-2.5 py-1 text-[9px] font-extrabold text-white backdrop-blur sm:bottom-3 sm:right-3 sm:text-[10px]"></span>
            </div>
            <div data-product-gallery-dots class="mt-2 flex justify-center gap-1.5 sm:mt-3" aria-label="Choose product picture"></div>
            <div class="px-4 pb-4 pt-3 sm:px-5 sm:pt-4">
                <div class="min-w-0"><p data-modal-code class="text-[9px] font-bold uppercase tracking-wider text-[#e7682b] sm:text-[10px]"></p><h2 id="product-detail-title" data-modal-name class="mt-0.5 text-lg font-extrabold leading-6 text-[#17324d] sm:mt-1 sm:text-xl"></h2></div>
                <div data-clicker-image-groups class="mt-3 hidden space-y-3">
                    <div data-clicker-casing-group class="hidden">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Casing <span class="text-red-500">*</span></p>
                            <button type="button" data-open-clicker-gallery="casing" class="text-[10px] font-bold text-[#e7682b] underline decoration-orange-200 underline-offset-2">Open bigger image</button>
                        </div>
                        <div data-clicker-casing-thumbs class="mt-1.5 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none]"></div>
                    </div>
                    <div data-clicker-huruf-group class="hidden">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Huruf <span class="text-red-500">*</span></p>
                            <button type="button" data-open-clicker-gallery="huruf" class="text-[10px] font-bold text-[#e7682b] underline decoration-orange-200 underline-offset-2">Open bigger image</button>
                        </div>
                        <div data-clicker-huruf-thumbs class="mt-1.5 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none]"></div>
                    </div>
                    <p data-clicker-image-hint class="text-[11px] font-semibold text-amber-700">Select one casing and one huruf.</p>
                </div>
                <div data-clicker-config class="mt-3 hidden rounded-2xl border border-orange-100 bg-orange-50/40 p-3">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-[#e7682b]">Total characters</p>
                        <p class="text-[10px] font-semibold text-slate-500">Pilih satu sahaja</p>
                    </div>
                    <div data-clicker-count-buttons class="mt-2 flex flex-wrap gap-2">
                        @foreach (range(1, 8) as $characterCount)
                            <button type="button" data-clicker-count="{{ $characterCount }}" class="grid h-9 min-w-9 place-items-center rounded-lg border border-slate-200 bg-slate-100 px-2 text-xs font-extrabold text-slate-600 transition focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-[#e7682b]">{{ $characterCount }}</button>
                        @endforeach
                    </div>
                    <p data-clicker-hint class="mt-2 text-[11px] text-slate-500">Pilih total characters untuk tetapkan harga seunit.</p>
                    <div class="mt-2 rounded-xl border border-orange-100 bg-white/80 p-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Input characters</p>
                        <div data-clicker-inputs class="mt-2 flex flex-wrap gap-2"></div>
                    </div>
                </div>
                <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2.5 sm:mt-4 sm:px-4 sm:py-3"><p class="text-[9px] font-bold uppercase text-slate-400 sm:text-[10px]">Price</p><p data-modal-price class="mt-0.5 text-lg font-black text-[#e7682b]"></p></div>
                <div data-modal-specs class="mt-3 hidden grid-cols-2 gap-x-4 gap-y-2 border-y border-slate-100 py-3"></div>
                <div class="mt-3 flex items-center justify-between gap-3 sm:mt-4"><div class="min-w-0"><p class="text-sm font-extrabold text-[#17324d]">Quantity</p><p data-modal-stock class="mt-0.5 truncate text-[11px] text-slate-400 sm:text-xs"></p></div><div class="flex flex-none items-center rounded-xl border border-slate-200 p-0.5 shadow-sm"><button type="button" data-quantity-minus class="grid h-9 w-9 place-items-center rounded-lg text-lg font-bold">−</button><input data-quantity-input type="number" min="1" value="1" inputmode="numeric" class="h-9 w-12 border-0 text-center text-base font-black outline-none sm:w-14"><button type="button" data-quantity-plus class="grid h-9 w-9 place-items-center rounded-lg text-lg font-bold">+</button></div></div>
            </div>
        </div>
        <div class="flex-none border-t border-slate-100 bg-white px-4 pt-3 sm:px-5" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
            <div class="flex items-center justify-between"><p class="text-xs font-bold text-slate-500 sm:text-sm">Item total</p><p data-modal-total class="text-xl font-black text-[#17324d]">RM 0.00</p></div>
            <button type="button" data-confirm-add class="mt-2.5 h-12 w-full rounded-xl bg-[#e7682b] text-sm font-extrabold text-white shadow-lg shadow-orange-600/20 sm:h-13 sm:rounded-2xl">Add to cart</button>
        </div>
    </div>
</div>

<div data-cart-modal class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/45 backdrop-blur-sm sm:items-center sm:p-5" role="dialog" aria-modal="true">
    <div class="flex max-h-[88vh] w-full max-w-md flex-col rounded-t-[2rem] bg-white shadow-2xl sm:rounded-[2rem]" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex items-center justify-between border-b border-slate-100 p-5"><div><p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#e7682b]">Order preview</p><h2 class="mt-1 text-xl font-extrabold text-[#17324d]">Your cart</h2></div><button type="button" data-close-cart class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-500" aria-label="Close"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
        <div data-cart-items class="min-h-24 flex-1 space-y-3 overflow-y-auto p-5"></div>
        <div class="border-t border-slate-100 p-5">
            <div class="flex justify-between text-sm"><span class="font-semibold text-slate-500">Total units</span><span data-review-units class="font-extrabold">0</span></div>
            <div class="mt-2 flex justify-between text-sm"><span class="font-semibold text-slate-500">Total amount</span><span data-review-subtotal class="font-bold text-slate-700">RM 0.00</span></div>
            <div class="mt-2 flex justify-between text-sm"><span class="font-semibold text-slate-500">Eligible discount</span><span data-review-discount class="font-bold text-emerald-600">- RM 0.00</span></div>
            <p data-review-discount-note class="mt-1 text-[10px] text-slate-400">No discount yet.</p>
            <div class="mt-2 flex items-end justify-between"><span class="font-semibold text-slate-500">Order total</span><span data-review-total class="text-2xl font-black text-[#e7682b]">RM 0.00</span></div>
            <button type="button" data-ui-checkout class="mt-5 h-13 w-full rounded-2xl bg-[#17324d] text-sm font-extrabold text-white">Proceed to checkout</button>
            <p class="mt-2 text-center text-[10px] text-slate-400">Review your selected items before checkout.</p>
        </div>
    </div>
</div>

<div data-clicker-preview-modal class="fixed inset-0 z-[70] hidden items-end justify-center bg-slate-950/70 backdrop-blur-sm sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="clicker-gallery-title">
    <div class="w-full max-w-2xl overflow-hidden rounded-t-[2rem] bg-white shadow-2xl sm:rounded-[2rem]">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 sm:px-5">
            <div>
                <p class="text-[9px] font-bold uppercase tracking-[0.14em] text-[#e7682b]">Choose design</p>
                <h3 id="clicker-gallery-title" data-clicker-preview-title class="mt-0.5 text-base font-extrabold text-[#17324d]">Casing pictures</h3>
            </div>
            <button type="button" data-close-clicker-preview class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-600" aria-label="Close image preview"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="p-4 sm:p-5">
            <div class="relative grid h-[min(58vh,34rem)] place-items-center overflow-hidden rounded-2xl bg-slate-100">
                <img data-clicker-preview-image src="" alt="" class="max-h-full w-full object-contain">
                <button type="button" data-clicker-preview-previous class="absolute left-2 grid h-10 w-10 place-items-center rounded-full bg-white/95 text-[#17324d] shadow-md" aria-label="Previous image"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg></button>
                <button type="button" data-clicker-preview-next class="absolute right-2 grid h-10 w-10 place-items-center rounded-full bg-white/95 text-[#17324d] shadow-md" aria-label="Next image"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg></button>
                <span data-clicker-preview-counter class="absolute bottom-3 right-3 rounded-full bg-slate-950/70 px-3 py-1 text-[10px] font-extrabold text-white backdrop-blur"></span>
            </div>
            <button type="button" data-select-clicker-preview class="mt-4 h-12 w-full rounded-xl bg-[#e7682b] text-sm font-extrabold text-white shadow-lg shadow-orange-600/20">Select this</button>
        </div>
    </div>
</div>

@include('agent.partials.checkout')

<style>
    [data-clicker-inputs] .clicker-character-input {
        color: #c94f16 !important;
        font-weight: 800 !important;
    }

    [data-clicker-inputs] .clicker-character-input::placeholder {
        color: #cbd5e1 !important;
        font-weight: 400 !important;
        opacity: 1;
    }
</style>

@push('scripts')
<script>
(() => {
    const cartKey = 'a3d-agent-cart-{{ $agent->getKey() }}';
    const currency = new Intl.NumberFormat('en-MY', {style: 'currency', currency: 'MYR'});
    const clickerCharacterPricesByProduct = @json($clickerCharacterPricesByProduct);
    const discountRules = @json(\App\Support\AgentOrderDiscount::frontendConfig((float) $agent->discount_percentage));
    const searchInput = document.querySelector('[data-product-search]');
    const quantityModal = document.querySelector('[data-quantity-modal]');
    const cartModal = document.querySelector('[data-cart-modal]');
    const checkoutModal = document.querySelector('[data-checkout-modal]');
    const successModal = document.querySelector('[data-checkout-success]');
    const quantityInput = document.querySelector('[data-quantity-input]');
    const clickerConfig = document.querySelector('[data-clicker-config]');
    const clickerHint = document.querySelector('[data-clicker-hint]');
    const clickerInputsContainer = document.querySelector('[data-clicker-inputs]');
    const clickerImagesByProduct = @json($clickerImagesByProduct);
    const clickerImageGroups = document.querySelector('[data-clicker-image-groups]');
    const clickerCasingGroup = document.querySelector('[data-clicker-casing-group]');
    const clickerHurufGroup = document.querySelector('[data-clicker-huruf-group]');
    const clickerCasingThumbs = document.querySelector('[data-clicker-casing-thumbs]');
    const clickerHurufThumbs = document.querySelector('[data-clicker-huruf-thumbs]');
    const clickerImageHint = document.querySelector('[data-clicker-image-hint]');
    const clickerPreviewModal = document.querySelector('[data-clicker-preview-modal]');
    const clickerPreviewImage = document.querySelector('[data-clicker-preview-image]');
    const clickerPreviewTitle = document.querySelector('[data-clicker-preview-title]');
    const clickerPreviewCounter = document.querySelector('[data-clicker-preview-counter]');
    const clickerPreviewPrevious = document.querySelector('[data-clicker-preview-previous]');
    const clickerPreviewNext = document.querySelector('[data-clicker-preview-next]');
    const selectClickerPreviewButton = document.querySelector('[data-select-clicker-preview]');
    const clickerDefaultPlaceholder = 'MUHAMMAD';
    let selectedProduct = null;
    let clickerPreviewType = null;
    let clickerPreviewImages = [];
    let clickerPreviewIndex = 0;
    let cart = {};
    try { cart = JSON.parse(localStorage.getItem(cartKey)) || {}; } catch (error) { cart = {}; }

    const values = () => Object.values(cart);
    const units = () => values().reduce((sum, item) => sum + Number(item.quantity), 0);
    const priceCents = (amount) => Math.round(Number(amount) * 100);
    const formatCents = (cents) => currency.format(cents / 100);
    const normalizeImageList = (images) => {
        if (!Array.isArray(images)) return [];

        return images
            .map((image) => {
                if (!image || typeof image !== 'object') return null;

                const src = String(image.src || '').trim();
                if (src === '') return null;

                return {
                    id: Number(image.id || 0),
                    src,
                    alt: String(image.alt || '').trim(),
                };
            })
            .filter(Boolean);
    };
    const normalizeClickerPrices = (prices) => {
        if (!prices || typeof prices !== 'object') return {};

        const normalized = {};
        Object.entries(prices).forEach(([key, value]) => {
            const count = Number(key);
            const amount = Number(value);
            if (Number.isInteger(count) && count >= 1 && count <= 8 && Number.isFinite(amount) && amount >= 0) {
                normalized[count] = amount;
            }
        });

        return normalized;
    };
    const sanitizeCharacter = (value) => {
        const text = String(value || '').trim().toUpperCase();
        if (text === '') return '';

        return Array.from(text)[0] || '';
    };
    const getClickerCharacters = (product) => {
        if (!product || !Array.isArray(product.clickerCharacters)) return [];

        return product.clickerCharacters.map((character) => sanitizeCharacter(character));
    };
    const resolveSelectedUnitPrice = (product) => {
        if (!product) return 0;

        if (product.productType !== 'clicker') {
            return Number(product.basePrice || product.price || 0);
        }

        const count = Number(product.clickerCharacterCount || 0);
        const mapped = Number(product.clickerPrices?.[count]);
        if (count >= 1 && Number.isFinite(mapped) && mapped >= 0) {
            return mapped;
        }

        return Number(product.basePrice || product.price || 0);
    };
    const clickerCharactersComplete = (product) => {
        if (!product || product.productType !== 'clicker') return true;

        const count = Number(product.clickerCharacterCount || 0);
        if (count < 1 || count > 8) return false;

        const characters = getClickerCharacters(product).slice(0, count).filter((character) => character.length === 1);

        return characters.length === count;
    };
    const clickerImagesComplete = (product) => {
        if (!product || product.productType !== 'clicker') return true;

        return Boolean(
            Number(product.clickerCasingSelection?.id || 0) > 0
            && Number(product.clickerHurufSelection?.id || 0) > 0
        );
    };
    const clickerConfigurationComplete = (product) => (
        clickerCharactersComplete(product) && clickerImagesComplete(product)
    );
    const clickerCharactersLabel = (item) => {
        if (item?.productType !== 'clicker') return null;

        const count = Number(item.clickerCharacterCount || 0);
        if (count < 1 || count > 8) return null;

        const text = getClickerCharacters(item).slice(0, count).join('');

        return text === '' ? `${count} characters` : `${count} characters: ${text}`;
    };
    const selectedFulfilmentMethod = () => document.querySelector('[data-checkout-form]')?.elements?.fulfilment_method?.value || 'delivery';
    const deliveryFeeCents = (fulfilmentMethod = selectedFulfilmentMethod()) => fulfilmentMethod === 'delivery' ? Number(discountRules.deliveryFeeCents) : 0;
    const subtotalCents = () => values().reduce((sum, item) => sum + (priceCents(item.price) * Number(item.quantity)), 0);
    const resolveDiscountPercentage = (grossSubtotalCents) => {
        if (grossSubtotalCents <= 0) return 0;
        if (grossSubtotalCents < discountRules.belowRm20ThresholdCents) return Number(discountRules.belowRm20Percentage);
        if (grossSubtotalCents < discountRules.belowRm100ThresholdCents) return Number(discountRules.belowRm100Percentage);
        return Number(discountRules.aboveRm100Percentage);
    };
    const discountedUnitCents = (item, discountPercentage) => {
        const discountTenths = Math.round(Number(discountPercentage) * 10);
        return Math.round(priceCents(item.price) * (1000 - discountTenths) / 1000);
    };
    const orderSummary = (fulfilmentMethod = selectedFulfilmentMethod()) => {
        const grossSubtotal = subtotalCents();
        const discountPercentage = resolveDiscountPercentage(grossSubtotal);
        const netSubtotal = values().reduce((sum, item) => {
            return sum + (discountedUnitCents(item, discountPercentage) * Number(item.quantity));
        }, 0);
        const deliveryCharge = deliveryFeeCents(fulfilmentMethod);

        return {
            grossSubtotal,
            discountPercentage,
            discountAmount: Math.max(0, grossSubtotal - netSubtotal),
            netSubtotal,
            deliveryCharge,
            orderTotal: netSubtotal + deliveryCharge,
        };
    };
    const close = (modal) => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        if (modal === clickerPreviewModal && clickerPreviewImage) {
            clickerPreviewImage.src = '';
            clickerPreviewImage.alt = '';
            clickerPreviewType = null;
            clickerPreviewImages = [];
            clickerPreviewIndex = 0;
        }

        if (modal === quantityModal) {
            document.body.classList.remove('overflow-hidden');
            window.dispatchEvent(new CustomEvent('product-gallery:close'));
            if (clickerPreviewModal && !clickerPreviewModal.classList.contains('hidden')) {
                close(clickerPreviewModal);
            }
        }
    };

    const selectedClickerImage = (type) => type === 'casing'
        ? selectedProduct?.clickerCasingSelection
        : selectedProduct?.clickerHurufSelection;

    const setSelectedClickerImage = (type, image) => {
        if (!selectedProduct || !image?.src) return;

        if (type === 'casing') {
            selectedProduct.clickerCasingSelection = image;
        } else {
            selectedProduct.clickerHurufSelection = image;
        }

        renderClickerImageGroups();
        updateModalTotal();
    };

    const renderClickerPreview = () => {
        const image = clickerPreviewImages[clickerPreviewIndex];
        if (!image || !clickerPreviewImage) return;

        clickerPreviewImage.src = image.src;
        clickerPreviewImage.alt = image.alt || selectedProduct?.name || 'Clicker image';
        clickerPreviewTitle.textContent = `${clickerPreviewType === 'casing' ? 'Casing' : 'Huruf'} pictures`;
        clickerPreviewCounter.textContent = `${clickerPreviewIndex + 1} / ${clickerPreviewImages.length}`;
        clickerPreviewPrevious.classList.toggle('hidden', clickerPreviewImages.length < 2);
        clickerPreviewNext.classList.toggle('hidden', clickerPreviewImages.length < 2);

        const isSelected = selectedClickerImage(clickerPreviewType)?.src === image.src;
        selectClickerPreviewButton.textContent = isSelected ? 'Selected' : 'Select this';
        selectClickerPreviewButton.classList.toggle('bg-emerald-600', isSelected);
        selectClickerPreviewButton.classList.toggle('bg-[#e7682b]', !isSelected);
    };

    const openClickerPreview = (type) => {
        const images = type === 'casing'
            ? normalizeImageList(selectedProduct?.clickerCasingImages)
            : normalizeImageList(selectedProduct?.clickerHurufImages);
        if (!clickerPreviewModal || images.length === 0) {
            return;
        }

        clickerPreviewType = type;
        clickerPreviewImages = images;
        const selectedSrc = selectedClickerImage(type)?.src;
        const selectedIndex = images.findIndex((image) => image.src === selectedSrc);
        clickerPreviewIndex = selectedIndex >= 0 ? selectedIndex : 0;
        renderClickerPreview();
        clickerPreviewModal.classList.remove('hidden');
        clickerPreviewModal.classList.add('flex');
    };

    const renderClickerThumbs = (container, images, type) => {
        if (!container) return;

        container.innerHTML = '';
        images.forEach((image, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            const isSelected = selectedClickerImage(type)?.src === image.src;
            button.className = `relative h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 bg-white shadow-sm transition active:scale-[0.98] ${isSelected ? 'border-emerald-500 ring-2 ring-emerald-100' : 'border-slate-200'}`;
            button.setAttribute('aria-label', `Select ${type} image ${index + 1}`);
            button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');

            const thumb = document.createElement('img');
            thumb.src = image.src;
            thumb.alt = image.alt || selectedProduct?.name || 'Clicker image';
            thumb.className = 'h-full w-full object-cover';

            button.appendChild(thumb);
            if (isSelected) {
                const selectedIcon = document.createElement('span');
                selectedIcon.className = 'absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 bg-emerald-600 px-1 py-1 text-[7px] font-black uppercase tracking-wide text-white';
                selectedIcon.innerHTML = '<svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg>Selected';
                button.appendChild(selectedIcon);
            }
            button.addEventListener('click', () => setSelectedClickerImage(type, image));
            container.appendChild(button);
        });
    };

    const renderClickerImageGroups = () => {
        if (!clickerImageGroups || !clickerCasingGroup || !clickerHurufGroup || !selectedProduct) {
            return;
        }

        const isClicker = selectedProduct.productType === 'clicker';
        const casingImages = isClicker ? normalizeImageList(selectedProduct.clickerCasingImages) : [];
        const hurufImages = isClicker ? normalizeImageList(selectedProduct.clickerHurufImages) : [];

        renderClickerThumbs(clickerCasingThumbs, casingImages, 'casing');
        renderClickerThumbs(clickerHurufThumbs, hurufImages, 'huruf');

        clickerCasingGroup.classList.toggle('hidden', casingImages.length === 0);
        clickerHurufGroup.classList.toggle('hidden', hurufImages.length === 0);
        clickerImageGroups.classList.toggle('hidden', !isClicker || (casingImages.length === 0 && hurufImages.length === 0));
        if (isClicker && clickerImageHint) {
            const complete = clickerImagesComplete(selectedProduct);
            clickerImageHint.textContent = complete ? 'Casing and huruf selected.' : 'Select one casing and one huruf.';
            clickerImageHint.classList.toggle('text-emerald-600', complete);
            clickerImageHint.classList.toggle('text-amber-700', !complete);
        }
    };

    const filterProducts = () => {
        const query = searchInput.value.trim().toLowerCase();
        let count = 0;
        document.querySelectorAll('[data-product-card]').forEach((card) => {
            const show = query === '' || card.dataset.searchValue.includes(query);
            card.classList.toggle('hidden', !show);
            card.classList.toggle('flex', show);
            if (show) count++;
        });
        document.querySelector('[data-result-count]').textContent = count;
        document.querySelector('[data-clear-search]').classList.toggle('hidden', query === '');
        document.querySelector('[data-no-results]').classList.toggle('hidden', count > 0);
    };

    const updateModalTotal = () => {
        if (!selectedProduct) return;
        const quantity = Math.max(1, Math.min(selectedProduct.max, Number(quantityInput.value) || 1));
        quantityInput.value = quantity;
        const selectedUnitPrice = resolveSelectedUnitPrice(selectedProduct);
        selectedProduct.price = selectedUnitPrice;
        document.querySelector('[data-modal-price]').textContent = currency.format(selectedUnitPrice);
        document.querySelector('[data-modal-total]').textContent = currency.format(quantity * selectedUnitPrice);
        const addButton = document.querySelector('[data-confirm-add]');
        addButton.disabled = !clickerConfigurationComplete(selectedProduct);
        addButton.classList.toggle('opacity-50', addButton.disabled);
        addButton.classList.toggle('cursor-not-allowed', addButton.disabled);
    };

    const updateClickerCountButtonState = () => {
        document.querySelectorAll('[data-clicker-count]').forEach((button) => {
            const active = Number(button.dataset.clickerCount) === Number(selectedProduct?.clickerCharacterCount || 0);
            button.classList.toggle('bg-[#e7682b]', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('border-[#e7682b]', active);
            button.classList.toggle('bg-slate-100', !active);
            button.classList.toggle('text-slate-600', !active);
            button.classList.toggle('border-slate-200', !active);
        });
    };

    const renderClickerInputs = () => {
        if (!selectedProduct || selectedProduct.productType !== 'clicker') {
            clickerInputsContainer.innerHTML = '';
            return;
        }

        const count = Number(selectedProduct.clickerCharacterCount || 0);
        const characters = getClickerCharacters(selectedProduct);

        clickerInputsContainer.innerHTML = '';
        if (count < 1 || count > 8) {
            clickerHint.textContent = 'Pilih satu total characters (1 hingga 8).';
            return;
        }

        const selectedUnitPrice = resolveSelectedUnitPrice(selectedProduct);
        clickerHint.textContent = `Harga seunit: ${currency.format(selectedUnitPrice)} untuk ${count} characters.`;

        const updateCharacterInputState = (input) => {
            const filled = String(input.value || '').trim() !== '';
            input.classList.toggle('bg-emerald-50', filled);
            input.classList.toggle('border-emerald-300', filled);
            input.classList.toggle('bg-white', !filled);
            input.classList.toggle('border-orange-200', !filled);
        };

        for (let index = 0; index < count; index += 1) {
            const input = document.createElement('input');
            input.type = 'text';
            input.maxLength = 1;
            input.inputMode = 'text';
            input.autocomplete = 'off';
            input.spellcheck = false;
            input.placeholder = clickerDefaultPlaceholder[index] || '';
            input.value = characters[index] || '';
            input.className = 'clicker-character-input h-10 w-10 shrink-0 rounded-lg border border-orange-200 bg-white text-center text-base font-extrabold uppercase text-[#c94f16] caret-[#c94f16] outline-none transition focus:border-[#e7682b] focus:ring-2 focus:ring-orange-200';
            updateCharacterInputState(input);
            input.addEventListener('input', () => {
                const value = sanitizeCharacter(input.value);
                input.value = value;
                selectedProduct.clickerCharacters[index] = value;
                updateCharacterInputState(input);
                updateModalTotal();

                if (value !== '') {
                    const nextInput = clickerInputsContainer.querySelectorAll('input')[index + 1];
                    nextInput?.focus();
                }
            });

            input.addEventListener('keydown', (event) => {
                if (event.key !== 'Backspace') {
                    return;
                }

                if (input.value !== '') {
                    return;
                }

                const previousInput = clickerInputsContainer.querySelectorAll('input')[index - 1];
                if (!previousInput) {
                    return;
                }

                event.preventDefault();
                previousInput.focus();
                previousInput.select();
            });

            clickerInputsContainer.append(input);
        }
    };

    const renderClickerConfig = () => {
        const isClicker = selectedProduct?.productType === 'clicker';
        clickerConfig.classList.toggle('hidden', !isClicker);
        if (!isClicker) {
            return;
        }

        updateClickerCountButtonState();
        renderClickerInputs();
    };

    const openProductDetails = (button) => {
        const productId = String(button.dataset.id);
        const restoredItem = cart[productId] || null;
        const fallbackClickerPrices = clickerCharacterPricesByProduct[productId] || clickerCharacterPricesByProduct[Number(productId)] || {};
        const fallbackClickerImages = clickerImagesByProduct[productId] || clickerImagesByProduct[Number(productId)] || {};
        selectedProduct = {
            id: productId, code: button.dataset.code, name: button.dataset.name,
            images: JSON.parse(button.dataset.images || '[]'), basePrice: Number(button.dataset.price),
            price: Number(button.dataset.price),
            max: Number(button.dataset.max), preorder: button.dataset.preorder === '1',
            material: button.dataset.material, color: button.dataset.color, weight: button.dataset.weight,
            width: button.dataset.width, height: button.dataset.height, length: button.dataset.length,
            productType: button.dataset.productType === 'clicker' ? 'clicker' : 'standard',
            clickerPrices: normalizeClickerPrices(JSON.parse(button.dataset.clickerPrices || JSON.stringify(fallbackClickerPrices || {}))),
            clickerCasingImages: normalizeImageList(JSON.parse(button.dataset.clickerCasingImages || JSON.stringify(fallbackClickerImages.casing || []))),
            clickerHurufImages: normalizeImageList(JSON.parse(button.dataset.clickerHurufImages || JSON.stringify(fallbackClickerImages.huruf || []))),
            clickerCasingSelection: restoredItem?.clickerCasingSelection || null,
            clickerHurufSelection: restoredItem?.clickerHurufSelection || null,
            clickerCharacterCount: Number(restoredItem?.clickerCharacterCount || 0),
            clickerCharacters: getClickerCharacters(restoredItem),
        };

        if (selectedProduct.productType === 'clicker' && (selectedProduct.clickerCharacterCount < 1 || selectedProduct.clickerCharacterCount > 8)) {
            selectedProduct.clickerCharacterCount = 1;
        }

        selectedProduct.price = resolveSelectedUnitPrice(selectedProduct);

        window.dispatchEvent(new CustomEvent('product-gallery:open', {
            detail: {
                images: selectedProduct.images,
                productName: selectedProduct.name,
            },
        }));

        const status = document.querySelector('[data-modal-status]');
        status.textContent = selectedProduct.preorder ? 'Pre-order' : 'In stock';
        status.className = `absolute left-2.5 top-2.5 rounded-full px-2.5 py-1 text-[9px] font-extrabold uppercase shadow-sm sm:left-3 sm:top-3 sm:px-3 sm:py-1.5 sm:text-[10px] ${selectedProduct.preorder ? 'bg-orange-50 text-[#e7682b]' : 'bg-emerald-50 text-emerald-700'}`;
        document.querySelector('[data-modal-code]').textContent = selectedProduct.code;
        document.querySelector('[data-modal-name]').textContent = selectedProduct.name;
        renderClickerImageGroups();
        document.querySelector('[data-modal-price]').textContent = currency.format(selectedProduct.price);
        document.querySelector('[data-modal-stock]').textContent = selectedProduct.preorder ? 'Pre-order item · choose required quantity' : `${selectedProduct.max} units available`;

        const dimensions = [selectedProduct.length, selectedProduct.width, selectedProduct.height].filter(Boolean);
        const specs = [
            ['Material', selectedProduct.material],
            ['Colour', selectedProduct.color],
            ['Weight', selectedProduct.weight ? `${selectedProduct.weight} g` : ''],
            ['Size (L × W × H)', dimensions.length === 3 ? `${dimensions.join(' × ')} mm` : ''],
        ].filter(([, value]) => value);
        const specsContainer = document.querySelector('[data-modal-specs]');
        specsContainer.innerHTML = '';
        specs.forEach(([label, value]) => {
            const item = document.createElement('div'); item.className = 'min-w-0';
            const caption = document.createElement('p'); caption.className = 'text-[9px] font-bold uppercase tracking-wider text-slate-400'; caption.textContent = label;
            const detail = document.createElement('p'); detail.className = 'mt-0.5 break-words text-[11px] font-extrabold leading-4 text-[#17324d] sm:text-xs'; detail.textContent = value;
            item.append(caption, detail); specsContainer.append(item);
        });
        specsContainer.classList.toggle('hidden', specs.length === 0);
        specsContainer.classList.toggle('grid', specs.length > 0);

        renderClickerConfig();

        quantityInput.max = selectedProduct.max;
        quantityInput.value = restoredItem?.quantity || 1;
        document.querySelector('[data-confirm-add]').textContent = cart[selectedProduct.id] ? 'Update cart' : (selectedProduct.preorder ? 'Add pre-order to cart' : 'Add to cart');
        updateModalTotal();
        quantityModal.classList.remove('hidden');
        quantityModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    const renderReview = () => {
        const container = document.querySelector('[data-cart-items]');
        container.innerHTML = '';
        values().forEach((item) => {
            const row = document.createElement('article'); row.className = 'flex items-center gap-3 rounded-2xl bg-slate-50 p-3';
            const detail = document.createElement('div'); detail.className = 'min-w-0 flex-1';
            const name = document.createElement('p'); name.className = 'truncate text-sm font-extrabold text-[#17324d]'; name.textContent = item.name;
            const clickerLabel = clickerCharactersLabel(item);
            if (clickerLabel) {
                const clickerInfo = document.createElement('p');
                clickerInfo.className = 'mt-0.5 text-[11px] font-semibold text-slate-500';
                clickerInfo.textContent = clickerLabel;
                detail.append(name, clickerInfo);
            } else {
                detail.append(name);
            }
            const price = document.createElement('p'); price.className = 'mt-1 text-xs font-bold text-[#e7682b]'; price.textContent = `${item.preorder ? 'Pre-order · ' : ''}${item.quantity} × ${currency.format(item.price)} = ${currency.format(item.quantity * item.price)}`;
            detail.append(price);
            const remove = document.createElement('button'); remove.type = 'button'; remove.dataset.removeCartItem = item.id; remove.className = 'grid h-9 w-9 place-items-center rounded-full bg-white text-red-500 shadow-sm'; remove.innerHTML = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M9 7V4h6v3m-9 0 1 14h10l1-14M10 11v6m4-6v6"/></svg>';
            row.append(detail, remove); container.append(row);
        });

        const summary = orderSummary();
        document.querySelector('[data-review-units]').textContent = units();
        document.querySelector('[data-review-subtotal]').textContent = formatCents(summary.grossSubtotal);
        document.querySelector('[data-review-discount]').textContent = `- ${formatCents(summary.discountAmount)}`;
        document.querySelector('[data-review-discount-note]').textContent = summary.discountPercentage > 0
            ? `${summary.discountPercentage.toFixed(1)}% discount applied based on cart total.`
            : 'No discount yet.';
        document.querySelector('[data-review-total]').textContent = formatCents(summary.netSubtotal);
    };

    const renderCheckout = () => {
        const container = document.querySelector('[data-checkout-items]');
        container.innerHTML = '';
        values().forEach((item) => {
            const row = document.createElement('div'); row.className = 'flex items-start justify-between gap-3 rounded-xl bg-slate-50 p-3';
            const detail = document.createElement('div'); detail.className = 'min-w-0 flex-1';
            const name = document.createElement('p'); name.className = 'truncate text-xs font-extrabold text-[#17324d]'; name.textContent = item.name;
            const caption = document.createElement('p'); caption.className = 'mt-1 text-[10px] text-slate-500'; caption.textContent = `${item.preorder ? 'Pre-order · ' : ''}${item.quantity} × ${currency.format(item.price)}`;
            const clickerLabel = clickerCharactersLabel(item);
            if (clickerLabel) {
                const clickerInfo = document.createElement('p');
                clickerInfo.className = 'mt-0.5 text-[10px] font-semibold text-slate-500';
                clickerInfo.textContent = clickerLabel;
                detail.append(name, caption, clickerInfo);
            } else {
                detail.append(name, caption);
            }
            const total = document.createElement('p'); total.className = 'flex-none text-xs font-black text-[#e7682b]'; total.textContent = currency.format(item.quantity * item.price);
            row.append(detail, total); container.append(row);
        });

        const summary = orderSummary();
        document.querySelector('[data-checkout-units]').textContent = `${units()} units`;
        document.querySelector('[data-checkout-subtotal]').textContent = formatCents(summary.grossSubtotal);
        document.querySelector('[data-checkout-discount]').textContent = `- ${formatCents(summary.discountAmount)}`;
        document.querySelector('[data-checkout-discount-note]').textContent = summary.discountPercentage > 0
            ? `${summary.discountPercentage.toFixed(1)}% discount applied based on total amount.`
            : 'No discount yet.';
        document.querySelector('[data-checkout-delivery]').textContent = summary.deliveryCharge > 0
            ? formatCents(summary.deliveryCharge)
            : 'No delivery charge';
        document.querySelector('[data-checkout-total]').textContent = formatCents(summary.orderTotal);
        document.querySelector('[data-confirm-total]').textContent = formatCents(summary.orderTotal);
        document.querySelector('[data-success-total]').textContent = formatCents(summary.orderTotal);
    };

    const renderCart = () => {
        const quantity = units();
        const summary = orderSummary();
        document.querySelectorAll('[data-cart-visible]').forEach((element) => { element.classList.toggle('hidden', quantity === 0); element.classList.toggle('flex', quantity > 0); });
        document.querySelectorAll('[data-cart-units]').forEach((element) => element.textContent = quantity);
        document.querySelectorAll('[data-cart-total]').forEach((element) => element.textContent = formatCents(summary.grossSubtotal));
        document.querySelectorAll('[data-product-card]').forEach((card) => {
            const item = cart[card.dataset.productId]; const badge = card.querySelector('[data-card-cart-badge]');
            badge.classList.toggle('hidden', !item); badge.classList.toggle('flex', Boolean(item));
            if (item) { card.querySelector('[data-card-cart-quantity]').textContent = item.quantity; card.querySelector('[data-add-label]').textContent = item.preorder ? 'Edit pre-order' : 'Edit quantity'; card.classList.add('border-orange-300', 'ring-2', 'ring-orange-100'); }
            else { card.querySelector('[data-add-label]').textContent = card.querySelector('[data-add-product]').dataset.preorder === '1' ? 'Pre-order' : 'Add'; card.classList.remove('border-orange-300', 'ring-2', 'ring-orange-100'); }
        });
        renderReview();
    };
    const save = () => { localStorage.setItem(cartKey, JSON.stringify(cart)); renderCart(); };

    searchInput.addEventListener('input', filterProducts);
    document.querySelector('[data-clear-search]').addEventListener('click', () => { searchInput.value = ''; filterProducts(); searchInput.focus(); });
    document.querySelectorAll('[data-product-card]').forEach((card) => {
        const button = card.querySelector('[data-add-product]');
        card.addEventListener('click', () => openProductDetails(button));
        card.addEventListener('keydown', (event) => {
            if (event.target !== card) return;
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openProductDetails(button); }
        });
        button.addEventListener('click', (event) => { event.stopPropagation(); openProductDetails(button); });
    });
    document.querySelector('[data-close-quantity]').addEventListener('click', () => close(quantityModal)); document.querySelector('[data-close-cart]').addEventListener('click', () => close(cartModal));
    document.querySelectorAll('[data-close-clicker-preview]').forEach((button) => {
        button.addEventListener('click', () => close(clickerPreviewModal));
    });
    if (clickerPreviewModal) {
        clickerPreviewModal.addEventListener('click', (event) => {
            if (event.target === clickerPreviewModal) {
                close(clickerPreviewModal);
            }
        });
    }
    document.querySelectorAll('[data-open-clicker-gallery]').forEach((button) => {
        button.addEventListener('click', () => openClickerPreview(button.dataset.openClickerGallery));
    });
    clickerPreviewPrevious?.addEventListener('click', () => {
        if (clickerPreviewImages.length < 2) return;
        clickerPreviewIndex = (clickerPreviewIndex - 1 + clickerPreviewImages.length) % clickerPreviewImages.length;
        renderClickerPreview();
    });
    clickerPreviewNext?.addEventListener('click', () => {
        if (clickerPreviewImages.length < 2) return;
        clickerPreviewIndex = (clickerPreviewIndex + 1) % clickerPreviewImages.length;
        renderClickerPreview();
    });
    selectClickerPreviewButton?.addEventListener('click', () => {
        const image = clickerPreviewImages[clickerPreviewIndex];
        if (!clickerPreviewType || !image) return;

        setSelectedClickerImage(clickerPreviewType, image);
        close(clickerPreviewModal);
    });
    document.querySelectorAll('[data-clicker-count]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!selectedProduct || selectedProduct.productType !== 'clicker') return;

            const nextCount = Number(button.dataset.clickerCount || 0);
            if (!Number.isInteger(nextCount) || nextCount < 1 || nextCount > 8) return;

            selectedProduct.clickerCharacterCount = nextCount;
            selectedProduct.clickerCharacters = getClickerCharacters(selectedProduct).slice(0, nextCount);
            renderClickerConfig();
            updateModalTotal();
        });
    });
    document.querySelector('[data-quantity-minus]').addEventListener('click', () => { quantityInput.value = Math.max(1, Number(quantityInput.value) - 1); updateModalTotal(); });
    document.querySelector('[data-quantity-plus]').addEventListener('click', () => { quantityInput.value = Math.min(Number(quantityInput.max), Number(quantityInput.value) + 1); updateModalTotal(); }); quantityInput.addEventListener('input', updateModalTotal);
    document.querySelector('[data-confirm-add]').addEventListener('click', () => {
        if (!selectedProduct) return;
        if (!clickerConfigurationComplete(selectedProduct)) return;

        const quantity = Math.max(1, Math.min(selectedProduct.max, Number(quantityInput.value) || 1));
        const clickerCharacterCount = selectedProduct.productType === 'clicker'
            ? Number(selectedProduct.clickerCharacterCount || 0)
            : null;
        const clickerCharacters = selectedProduct.productType === 'clicker'
            ? getClickerCharacters(selectedProduct).slice(0, clickerCharacterCount || 0)
            : [];

        cart[selectedProduct.id] = {
            id: selectedProduct.id,
            code: selectedProduct.code,
            name: selectedProduct.name,
            images: selectedProduct.images,
            basePrice: Number(selectedProduct.basePrice || 0),
            price: resolveSelectedUnitPrice(selectedProduct),
            max: selectedProduct.max,
            preorder: selectedProduct.preorder,
            material: selectedProduct.material,
            color: selectedProduct.color,
            weight: selectedProduct.weight,
            width: selectedProduct.width,
            height: selectedProduct.height,
            length: selectedProduct.length,
            productType: selectedProduct.productType,
            clickerPrices: selectedProduct.clickerPrices,
            clickerCharacterCount,
            clickerCasingSelection: selectedProduct.clickerCasingSelection,
            clickerHurufSelection: selectedProduct.clickerHurufSelection,
            clickerCharacters,
            quantity,
        };
        save();
        close(quantityModal);
    });
    document.querySelectorAll('[data-open-cart]').forEach((button) => button.addEventListener('click', () => { renderReview(); cartModal.classList.remove('hidden'); cartModal.classList.add('flex'); }));
    document.querySelector('[data-cart-items]').addEventListener('click', (event) => { const button = event.target.closest('[data-remove-cart-item]'); if (!button) return; delete cart[button.dataset.removeCartItem]; save(); if (!units()) close(cartModal); });
    document.querySelector('[data-ui-checkout]').addEventListener('click', () => {
        renderCheckout();
        close(cartModal);
        checkoutModal.classList.remove('hidden');
        checkoutModal.classList.add('flex');
    });
    @include('agent.partials.checkout-script')
    quantityModal.addEventListener('click', (event) => { if (event.target === quantityModal) close(quantityModal); }); cartModal.addEventListener('click', (event) => { if (event.target === cartModal) close(cartModal); });
    renderCart(); filterProducts();
})();
</script>
@endpush
