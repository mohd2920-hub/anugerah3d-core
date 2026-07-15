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
                    $picture = $product->prd_picture ? (filter_var($product->prd_picture, FILTER_VALIDATE_URL) ? $product->prd_picture : asset($product->prd_picture)) : null;
                    $discount = (float) $agent->discount_percentage > 0 ? (float) $agent->discount_percentage : (float) $product->agent_discount_default;
                    $agentPrice = (float) $product->price_selling * (1 - ($discount / 100));
                    $searchValue = Str::lower($product->prd_code.' '.$product->prd_name);
                    $isPreOrder = (int) $product->prd_balance <= 0;
                @endphp
                <article data-product-card data-product-id="{{ $product->getKey() }}" data-search-value="{{ $searchValue }}" class="flex min-w-0 flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition">
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
                        <div class="mt-3 space-y-1"><div class="flex items-center justify-between gap-1 text-[9px]"><span class="text-slate-400">Selling</span><span class="font-bold text-slate-500 line-through">RM {{ number_format((float) $product->price_selling, 2) }}</span></div><div class="flex items-end justify-between gap-1"><span class="text-[9px] font-semibold text-slate-500">Agent price</span><span class="text-base font-black text-[#e7682b]">RM {{ number_format($agentPrice, 2) }}</span></div></div>
                        <button type="button" data-add-product data-id="{{ $product->getKey() }}" data-code="{{ $product->prd_code }}" data-name="{{ $product->prd_name }}" data-selling-price="{{ number_format((float) $product->price_selling, 2, '.', '') }}" data-agent-price="{{ number_format($agentPrice, 2, '.', '') }}" data-max="{{ $isPreOrder ? 9999 : (int) $product->prd_balance }}" data-preorder="{{ $isPreOrder ? '1' : '0' }}" @class(['mt-3 h-10 w-full rounded-xl text-xs font-extrabold text-white transition active:scale-[0.98]', 'bg-[#17324d]' => ! $isPreOrder, 'bg-[#e7682b]' => $isPreOrder])><span data-add-label>{{ $isPreOrder ? 'Pre-order' : 'Add' }}</span></button>
                    </div>
                </article>
            @endforeach
        </div>
        <div data-no-results class="hidden rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">No available products match your search.</div>
    </div>

    <button type="button" data-open-cart data-cart-visible class="hidden h-13 w-full items-center justify-center gap-2 rounded-2xl bg-[#e7682b] text-sm font-extrabold text-white shadow-lg shadow-orange-600/20"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M3 4h2l2.5 11h10.8l2-7H7"/></svg>Checkout · <span data-cart-units>0</span> units · <span data-cart-total>RM 0.00</span></button>
</section>

<div data-quantity-modal class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/45 backdrop-blur-sm sm:items-center sm:p-5" role="dialog" aria-modal="true">
    <div class="w-full max-w-md rounded-t-[2rem] bg-white p-5 shadow-2xl sm:rounded-[2rem]" style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom));">
        <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p data-modal-code class="text-[10px] font-bold uppercase tracking-wider text-[#e7682b]"></p><h2 data-modal-name class="mt-1 text-lg font-extrabold text-[#17324d]"></h2></div><button type="button" data-close-quantity class="grid h-10 w-10 flex-none place-items-center rounded-full bg-slate-100 text-slate-500" aria-label="Close"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
        <div class="mt-5 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-4"><div><p class="text-[10px] font-bold uppercase text-slate-400">Selling price</p><p data-modal-selling class="mt-1 text-sm font-bold text-slate-500 line-through"></p></div><div class="text-right"><p class="text-[10px] font-bold uppercase text-slate-400">Agent price</p><p data-modal-price class="mt-1 text-lg font-black text-[#e7682b]"></p></div></div>
        <div class="mt-5 flex items-center justify-between gap-4"><div><p class="text-sm font-extrabold text-[#17324d]">Quantity</p><p data-modal-stock class="mt-1 text-xs text-slate-400"></p></div><div class="flex items-center rounded-2xl border border-slate-200 p-1 shadow-sm"><button type="button" data-quantity-minus class="grid h-10 w-10 place-items-center rounded-xl text-lg font-bold">−</button><input data-quantity-input type="number" min="1" value="1" inputmode="numeric" class="h-10 w-14 border-0 text-center text-base font-black outline-none"><button type="button" data-quantity-plus class="grid h-10 w-10 place-items-center rounded-xl text-lg font-bold">+</button></div></div>
        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4"><p class="text-sm font-bold text-slate-500">Item total</p><p data-modal-total class="text-xl font-black text-[#17324d]">RM 0.00</p></div>
        <button type="button" data-confirm-add class="mt-5 h-13 w-full rounded-2xl bg-[#e7682b] text-sm font-extrabold text-white shadow-lg shadow-orange-600/20">Add to cart</button>
    </div>
</div>

<div data-cart-modal class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/45 backdrop-blur-sm sm:items-center sm:p-5" role="dialog" aria-modal="true">
    <div class="flex max-h-[88vh] w-full max-w-md flex-col rounded-t-[2rem] bg-white shadow-2xl sm:rounded-[2rem]" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex items-center justify-between border-b border-slate-100 p-5"><div><p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#e7682b]">Order preview</p><h2 class="mt-1 text-xl font-extrabold text-[#17324d]">Your cart</h2></div><button type="button" data-close-cart class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-500" aria-label="Close"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
        <div data-cart-items class="min-h-24 flex-1 space-y-3 overflow-y-auto p-5"></div>
        <div class="border-t border-slate-100 p-5"><div class="flex justify-between text-sm"><span class="font-semibold text-slate-500">Total units</span><span data-review-units class="font-extrabold">0</span></div><div class="mt-2 flex items-end justify-between"><span class="font-semibold text-slate-500">Total amount</span><span data-review-total class="text-2xl font-black text-[#e7682b]">RM 0.00</span></div><button type="button" data-ui-checkout class="mt-5 h-13 w-full rounded-2xl bg-[#17324d] text-sm font-extrabold text-white">Proceed to checkout</button><p class="mt-2 text-center text-[10px] text-slate-400">Review your selected items before checkout.</p></div>
    </div>
</div>
@include('agent.partials.checkout')

@push('scripts')
<script>
(() => {
    const cartKey = 'a3d-agent-cart-{{ $agent->getKey() }}';
    const currency = new Intl.NumberFormat('en-MY', {style: 'currency', currency: 'MYR'});
    const searchInput = document.querySelector('[data-product-search]');
    const quantityModal = document.querySelector('[data-quantity-modal]');
    const cartModal = document.querySelector('[data-cart-modal]');
    const checkoutModal = document.querySelector('[data-checkout-modal]');
    const successModal = document.querySelector('[data-checkout-success]');
    const quantityInput = document.querySelector('[data-quantity-input]');
    let selectedProduct = null;
    let cart = {};
    try { cart = JSON.parse(localStorage.getItem(cartKey)) || {}; } catch (error) { cart = {}; }

    const values = () => Object.values(cart);
    const units = () => values().reduce((sum, item) => sum + Number(item.quantity), 0);
    const amount = () => values().reduce((sum, item) => sum + Number(item.price) * Number(item.quantity), 0);
    const close = (modal) => { modal.classList.add('hidden'); modal.classList.remove('flex'); };

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
        document.querySelector('[data-modal-total]').textContent = currency.format(quantity * selectedProduct.price);
    };

    const renderReview = () => {
        const container = document.querySelector('[data-cart-items]');
        container.innerHTML = '';
        values().forEach((item) => {
            const row = document.createElement('article'); row.className = 'flex items-center gap-3 rounded-2xl bg-slate-50 p-3';
            const detail = document.createElement('div'); detail.className = 'min-w-0 flex-1';
            const name = document.createElement('p'); name.className = 'truncate text-sm font-extrabold text-[#17324d]'; name.textContent = item.name;
            const price = document.createElement('p'); price.className = 'mt-1 text-xs font-bold text-[#e7682b]'; price.textContent = `${item.preorder ? 'Pre-order · ' : ''}${item.quantity} × ${currency.format(item.price)} = ${currency.format(item.quantity * item.price)}`;
            detail.append(name, price);
            const remove = document.createElement('button'); remove.type = 'button'; remove.dataset.removeCartItem = item.id; remove.className = 'grid h-9 w-9 place-items-center rounded-full bg-white text-red-500 shadow-sm'; remove.innerHTML = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M9 7V4h6v3m-9 0 1 14h10l1-14M10 11v6m4-6v6"/></svg>';
            row.append(detail, remove); container.append(row);
        });
        document.querySelector('[data-review-units]').textContent = units();
        document.querySelector('[data-review-total]').textContent = currency.format(amount());
    };

    const renderCheckout = () => {
        const container = document.querySelector('[data-checkout-items]');
        container.innerHTML = '';
        values().forEach((item) => {
            const row = document.createElement('div'); row.className = 'flex items-start justify-between gap-3 rounded-xl bg-slate-50 p-3';
            const detail = document.createElement('div'); detail.className = 'min-w-0 flex-1';
            const name = document.createElement('p'); name.className = 'truncate text-xs font-extrabold text-[#17324d]'; name.textContent = item.name;
            const caption = document.createElement('p'); caption.className = 'mt-1 text-[10px] text-slate-500'; caption.textContent = `${item.preorder ? 'Pre-order · ' : ''}${item.quantity} × ${currency.format(item.price)}`;
            const total = document.createElement('p'); total.className = 'flex-none text-xs font-black text-[#e7682b]'; total.textContent = currency.format(item.quantity * item.price);
            detail.append(name, caption); row.append(detail, total); container.append(row);
        });
        const formattedTotal = currency.format(amount());
        document.querySelector('[data-checkout-units]').textContent = `${units()} units`;
        document.querySelector('[data-checkout-subtotal]').textContent = formattedTotal;
        document.querySelector('[data-checkout-total]').textContent = formattedTotal;
        document.querySelector('[data-confirm-total]').textContent = formattedTotal;
        document.querySelector('[data-success-total]').textContent = formattedTotal;
    };

    const renderCart = () => {
        const quantity = units();
        document.querySelectorAll('[data-cart-visible]').forEach((element) => { element.classList.toggle('hidden', quantity === 0); element.classList.toggle('flex', quantity > 0); });
        document.querySelectorAll('[data-cart-units]').forEach((element) => element.textContent = quantity);
        document.querySelectorAll('[data-cart-total]').forEach((element) => element.textContent = currency.format(amount()));
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
    document.querySelectorAll('[data-add-product]').forEach((button) => button.addEventListener('click', () => {
        selectedProduct = {id: button.dataset.id, code: button.dataset.code, name: button.dataset.name, sellingPrice: Number(button.dataset.sellingPrice), price: Number(button.dataset.agentPrice), max: Number(button.dataset.max), preorder: button.dataset.preorder === '1'};
        document.querySelector('[data-modal-code]').textContent = selectedProduct.code; document.querySelector('[data-modal-name]').textContent = selectedProduct.name; document.querySelector('[data-modal-selling]').textContent = currency.format(selectedProduct.sellingPrice); document.querySelector('[data-modal-price]').textContent = currency.format(selectedProduct.price); document.querySelector('[data-modal-stock]').textContent = selectedProduct.preorder ? 'Pre-order item · choose required quantity' : `${selectedProduct.max} units available`;
        quantityInput.max = selectedProduct.max; quantityInput.value = cart[selectedProduct.id]?.quantity || 1; document.querySelector('[data-confirm-add]').textContent = cart[selectedProduct.id] ? 'Update cart' : (selectedProduct.preorder ? 'Add pre-order to cart' : 'Add to cart'); updateModalTotal(); quantityModal.classList.remove('hidden'); quantityModal.classList.add('flex');
    }));
    document.querySelector('[data-close-quantity]').addEventListener('click', () => close(quantityModal)); document.querySelector('[data-close-cart]').addEventListener('click', () => close(cartModal));
    document.querySelector('[data-quantity-minus]').addEventListener('click', () => { quantityInput.value = Math.max(1, Number(quantityInput.value) - 1); updateModalTotal(); });
    document.querySelector('[data-quantity-plus]').addEventListener('click', () => { quantityInput.value = Math.min(Number(quantityInput.max), Number(quantityInput.value) + 1); updateModalTotal(); }); quantityInput.addEventListener('input', updateModalTotal);
    document.querySelector('[data-confirm-add]').addEventListener('click', () => { cart[selectedProduct.id] = {...selectedProduct, quantity: Number(quantityInput.value)}; save(); close(quantityModal); });
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
