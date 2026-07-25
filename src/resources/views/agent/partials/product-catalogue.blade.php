<section id="catalogue" class="scroll-mt-20 space-y-4" data-catalogue-root>
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#e7682b]">Product catalogue</p>
        <h2 class="mt-1 text-lg font-extrabold text-[#17324d]">Catalogue</h2>
        <p class="mt-1 text-xs leading-5 text-slate-500">Browse available and pre-order products. Use search to filter by product name or code.</p>
    </div>

    <div class="sticky top-[72px] z-20 -mx-1 rounded-3xl bg-[#f7f9fa]/95 px-1 py-2 backdrop-blur">
        <label class="relative block">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input data-catalogue-search type="search" autocomplete="off" placeholder="Search product name or code" class="h-12 w-full rounded-2xl border border-slate-200 bg-white pl-12 pr-4 text-sm outline-none shadow-sm transition focus:border-[#e7682b] focus:ring-4 focus:ring-orange-100">
        </label>
    </div>

    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <p class="text-sm font-extrabold text-[#17324d]"><span data-catalogue-count>{{ $products->count() }}</span> products</p>
            <button type="button" data-clear-catalogue-search class="hidden text-xs font-bold text-[#e7682b]">Clear</button>
        </div>

        <div class="grid grid-cols-2 gap-3">
            @foreach ($products as $product)
                @php
                    $legacyPicture = $product->prd_picture
                        ? (filter_var($product->prd_picture, FILTER_VALIDATE_URL) ? $product->prd_picture : asset(ltrim($product->prd_picture, '/')))
                        : null;
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
                    $searchValue = Str::lower($product->prd_code.' '.$product->prd_name);
                    $isPreOrder = (int) $product->prd_balance <= 0;
                @endphp
                <article id="catalogue-product-{{ $product->id }}" data-catalogue-card data-search-value="{{ $searchValue }}" data-product-name="{{ $product->prd_name }}" data-gallery-images="{{ $galleryImages->toJson() }}" class="scroll-mt-28 flex min-w-0 flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="relative aspect-square overflow-hidden bg-slate-100">
                        @if ($galleryImages->isNotEmpty())
                            <div data-catalogue-gallery class="flex h-full w-full snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none]">
                                @foreach ($galleryImages as $image)
                                    <button type="button" data-catalogue-slide data-image-index="{{ $loop->index }}" class="h-full min-w-full snap-center" aria-label="Open picture {{ $loop->iteration }} of {{ $galleryImages->count() }} for {{ $product->prd_name }}">
                                        <img src="{{ $image['src'] }}" alt="{{ $image['alt'] }}" loading="lazy" class="h-full w-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                            @if ($galleryImages->count() > 1)
                                <div class="pointer-events-none absolute inset-x-0 bottom-2 z-20 flex justify-center gap-1.5" data-catalogue-dots aria-label="Choose product picture">
                                    @foreach ($galleryImages as $image)
                                        <button type="button" data-catalogue-dot data-image-index="{{ $loop->index }}" class="pointer-events-auto h-1.5 rounded-full bg-white/70 shadow-sm transition-all {{ $loop->first ? 'w-4 bg-white' : 'w-1.5' }}" aria-label="Show picture {{ $loop->iteration }}"></button>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="grid h-full place-items-center bg-[linear-gradient(145deg,#f1f5f9,#e8eef5)] text-slate-300"><svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m4 16 4-4 4 4 3-3 5 5"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg></div>
                        @endif
                        @if ($isPreOrder)
                            <div class="pointer-events-none absolute inset-0 z-10 grid place-items-center bg-slate-950/50 p-3 text-center backdrop-blur-[1px]"><span class="rounded-full border border-white/20 bg-slate-950/70 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wider text-white shadow-lg">Out of stock</span></div>
                        @else
                            <span class="pointer-events-none absolute left-2 top-2 z-10 rounded-full bg-emerald-50 px-2 py-1 text-[8px] font-extrabold uppercase text-emerald-700 shadow-sm">In stock</span>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col p-3">
                        <p class="truncate text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ $product->prd_code }}</p>
                        <h3 class="mt-1 line-clamp-2 min-h-10 text-sm font-extrabold leading-5 text-[#17324d]">{{ $product->prd_name }}</h3>
                        <div class="mt-3 flex items-end justify-between gap-2 border-t border-slate-100 pt-3">
                            <span class="text-[9px] font-semibold text-slate-500">Selling price</span>
                            <span class="text-base font-black text-[#e7682b]">RM {{ number_format((float) $product->price_selling, 2) }}</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div data-no-catalogue-results class="hidden rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">No products match your search.</div>
    </div>
</section>

<div data-catalogue-image-modal class="hidden items-center justify-center" style="position: fixed; inset: 0; z-index: 2147483000; width: 100vw; height: 100dvh; background: rgba(2, 6, 23, .97);" role="dialog" aria-modal="true" aria-labelledby="catalogue-image-title">
    <button type="button" data-close-catalogue-image class="absolute inset-0" aria-label="Close picture viewer"></button>
    <div class="pointer-events-none relative flex flex-col" style="width: 100vw; height: 100dvh;">
        <header class="pointer-events-auto flex flex-none items-center justify-between gap-3 px-4 py-3 pr-16 text-white" style="padding-top: max(.75rem, env(safe-area-inset-top));">
            <div class="min-w-0 flex-1">
                <p id="catalogue-image-title" data-catalogue-modal-name class="truncate text-sm font-extrabold"></p>
                <p data-catalogue-modal-counter class="mt-0.5 text-[10px] font-semibold text-white/60"></p>
            </div>
        </header>

        <button type="button" data-close-catalogue-image class="pointer-events-auto grid h-11 w-11 place-items-center rounded-full bg-white text-2xl leading-none text-[#17324d] shadow-2xl" style="position: absolute; top: max(.75rem, env(safe-area-inset-top)); right: .75rem; z-index: 2147483002;" aria-label="Close picture viewer">×</button>

        <div class="relative min-h-0 flex-1">
            <img data-catalogue-modal-image src="" alt="" class="pointer-events-auto h-full w-full object-contain">
            <button type="button" data-catalogue-modal-previous class="pointer-events-auto absolute left-3 top-1/2 hidden h-11 w-11 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-[#17324d] shadow-xl" aria-label="Previous picture"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg></button>
            <button type="button" data-catalogue-modal-next class="pointer-events-auto absolute right-3 top-1/2 hidden h-11 w-11 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-[#17324d] shadow-xl" aria-label="Next picture"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg></button>
        </div>

        <div data-catalogue-modal-dots class="pointer-events-auto flex min-h-10 flex-none items-center justify-center gap-2 px-4 pb-3" style="padding-bottom: max(.75rem, env(safe-area-inset-bottom));"></div>
    </div>
</div>

@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-catalogue-root]');
    if (!root) return;

    const searchInput = root.querySelector('[data-catalogue-search]');
    const clearSearch = root.querySelector('[data-clear-catalogue-search]');
    const resultCount = root.querySelector('[data-catalogue-count]');
    const noResults = root.querySelector('[data-no-catalogue-results]');
    const imageModal = document.querySelector('[data-catalogue-image-modal]');
    const modalImage = imageModal.querySelector('[data-catalogue-modal-image]');
    const modalName = imageModal.querySelector('[data-catalogue-modal-name]');
    const modalCounter = imageModal.querySelector('[data-catalogue-modal-counter]');
    const modalDots = imageModal.querySelector('[data-catalogue-modal-dots]');
    const previousButton = imageModal.querySelector('[data-catalogue-modal-previous]');
    const nextButton = imageModal.querySelector('[data-catalogue-modal-next]');
    let modalImages = [];
    let modalIndex = 0;

    if (imageModal.parentElement !== document.body) {
        document.body.appendChild(imageModal);
    }

    const filterProducts = () => {
        const query = searchInput.value.trim().toLowerCase();
        let count = 0;

        root.querySelectorAll('[data-catalogue-card]').forEach((card) => {
            const isVisible = query === '' || card.dataset.searchValue.includes(query);
            card.classList.toggle('hidden', !isVisible);
            card.classList.toggle('flex', isVisible);
            if (isVisible) count++;
        });

        resultCount.textContent = count;
        clearSearch.classList.toggle('hidden', query === '');
        noResults.classList.toggle('hidden', count > 0);
    };

    searchInput.addEventListener('input', filterProducts);
    clearSearch.addEventListener('click', () => {
        searchInput.value = '';
        filterProducts();
        searchInput.focus();
    });

    const setActiveCardDot = (card, activeIndex) => {
        card.querySelectorAll('[data-catalogue-dot]').forEach((dot, index) => {
            const isActive = index === activeIndex;
            dot.classList.toggle('w-4', isActive);
            dot.classList.toggle('w-1.5', !isActive);
            dot.classList.toggle('bg-white', isActive);
            dot.classList.toggle('bg-white/70', !isActive);
        });
    };

    root.querySelectorAll('[data-catalogue-card]').forEach((card) => {
        const gallery = card.querySelector('[data-catalogue-gallery]');
        if (!gallery) return;

        let scrollTimer = null;
        gallery.addEventListener('scroll', () => {
            if (scrollTimer) window.clearTimeout(scrollTimer);
            scrollTimer = window.setTimeout(() => {
                const activeIndex = Math.round(gallery.scrollLeft / Math.max(1, gallery.clientWidth));
                setActiveCardDot(card, activeIndex);
            }, 60);
        }, { passive: true });

        card.querySelectorAll('[data-catalogue-dot]').forEach((dot) => {
            dot.addEventListener('click', () => {
                const index = Number(dot.dataset.imageIndex || 0);
                gallery.scrollTo({ left: gallery.clientWidth * index, behavior: 'smooth' });
                setActiveCardDot(card, index);
            });
        });
    });

    const renderImageModal = () => {
        const image = modalImages[modalIndex];
        if (!image) return;

        modalImage.src = image.src;
        modalImage.alt = image.alt || modalName.textContent;
        modalCounter.textContent = `${modalIndex + 1} of ${modalImages.length}`;
        previousButton.classList.toggle('hidden', modalImages.length <= 1);
        previousButton.classList.toggle('grid', modalImages.length > 1);
        nextButton.classList.toggle('hidden', modalImages.length <= 1);
        nextButton.classList.toggle('grid', modalImages.length > 1);
        modalDots.replaceChildren();

        if (modalImages.length > 1) {
            modalImages.forEach((item, index) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = index === modalIndex ? 'h-2 w-6 rounded-full bg-white' : 'h-2 w-2 rounded-full bg-white/35';
                dot.setAttribute('aria-label', `Show picture ${index + 1}`);
                dot.addEventListener('click', () => {
                    modalIndex = index;
                    renderImageModal();
                });
                modalDots.appendChild(dot);
            });
        }
    };

    const openImageModal = (card, index) => {
        try {
            modalImages = JSON.parse(card.dataset.galleryImages || '[]');
        } catch (error) {
            modalImages = [];
        }

        if (modalImages.length === 0) return;
        modalIndex = Math.max(0, Math.min(index, modalImages.length - 1));
        modalName.textContent = card.dataset.productName || '';
        renderImageModal();
        imageModal.classList.remove('hidden');
        imageModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    const closeImageModal = () => {
        imageModal.classList.add('hidden');
        imageModal.classList.remove('flex');
        modalImage.src = '';
        document.body.classList.remove('overflow-hidden');
    };

    root.querySelectorAll('[data-catalogue-slide]').forEach((slide) => {
        slide.addEventListener('click', () => {
            openImageModal(slide.closest('[data-catalogue-card]'), Number(slide.dataset.imageIndex || 0));
        });
    });

    imageModal.querySelectorAll('[data-close-catalogue-image]').forEach((button) => button.addEventListener('click', closeImageModal));
    previousButton.addEventListener('click', () => {
        modalIndex = (modalIndex - 1 + modalImages.length) % modalImages.length;
        renderImageModal();
    });
    nextButton.addEventListener('click', () => {
        modalIndex = (modalIndex + 1) % modalImages.length;
        renderImageModal();
    });
    document.addEventListener('keydown', (event) => {
        if (imageModal.classList.contains('hidden')) return;
        if (event.key === 'Escape') closeImageModal();
        if (event.key === 'ArrowLeft' && modalImages.length > 1) previousButton.click();
        if (event.key === 'ArrowRight' && modalImages.length > 1) nextButton.click();
    });
})();
</script>
@endpush
