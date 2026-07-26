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
            <p class="text-sm font-extrabold text-[#17324d]"><span data-catalogue-count>{{ $products->count() }}</span> / {{ $catalogueTotal ?? $products->count() }} products</p>
            <button type="button" data-clear-catalogue-search class="hidden text-xs font-bold text-[#e7682b]">Clear</button>
        </div>

        <div data-catalogue-grid class="grid grid-cols-2 gap-3">
            @include('agent.partials._catalogue-cards', ['products' => $products])
        </div>

        <div data-catalogue-loading class="{{ ($catalogueHasMore ?? false) ? 'flex' : 'hidden' }} items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3">
            <img src="{{ asset('images/loading.gif') }}" alt="Loading products" class="h-5 w-5">
            <span class="text-xs font-semibold text-slate-500">Loading more products...</span>
        </div>

        <div data-catalogue-sentinel class="h-8"></div>

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
    const grid = root.querySelector('[data-catalogue-grid]');
    const loading = root.querySelector('[data-catalogue-loading]');
    const sentinel = root.querySelector('[data-catalogue-sentinel]');
    const endpoint = @json(route('agent.dashboard.products'));
    let nextPage = {{ ($catalogueHasMore ?? false) ? 2 : 'null' }};
    let isLoading = false;
    let query = '';
    let debounceTimer = null;
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

    const setLoading = (value) => {
        isLoading = value;
        loading.classList.toggle('hidden', !value && nextPage === null);
        loading.classList.toggle('flex', value || nextPage !== null);
    };

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    };

    const fetchProducts = async ({ reset = false } = {}) => {
        if (isLoading) return;

        const targetPage = reset ? 1 : nextPage;
        if (targetPage === null) return;

        setLoading(true);

        try {
            const response = await fetch(`${endpoint}?page=${targetPage}&search=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (reset) {
                grid.innerHTML = '';
            }

            if (data.html) {
                grid.insertAdjacentHTML('beforeend', data.html);
            }

            initializeCards();
            nextPage = data.next_page;
            resultCount.textContent = String(grid.querySelectorAll('[data-catalogue-card]').length);
            noResults.classList.toggle('hidden', grid.querySelectorAll('[data-catalogue-card]').length > 0);
        } catch (error) {
            noResults.classList.remove('hidden');
            noResults.textContent = 'Unable to load products. Please try again.';
        } finally {
            setLoading(false);
        }
    };

    searchInput.addEventListener('input', () => {
        query = searchInput.value.trim().toLowerCase();
        clearSearch.classList.toggle('hidden', query === '');

        if (debounceTimer) {
            window.clearTimeout(debounceTimer);
        }

        debounceTimer = window.setTimeout(() => {
            nextPage = 1;
            fetchProducts({ reset: true });
        }, 250);
    });

    clearSearch.addEventListener('click', () => {
        searchInput.value = '';
        query = '';
        nextPage = 1;
        fetchProducts({ reset: true });
        clearSearch.classList.add('hidden');
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

    const initializeCards = () => {
        root.querySelectorAll('[data-catalogue-card]').forEach((card) => {
            if (card.dataset.initialized === '1') {
                return;
            }

            card.dataset.initialized = '1';
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
    };

    initializeCards();

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

    root.addEventListener('click', (event) => {
        const slide = event.target.closest('[data-catalogue-slide]');
        if (!slide) {
            return;
        }

        openImageModal(slide.closest('[data-catalogue-card]'), Number(slide.dataset.imageIndex || 0));
    });

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && nextPage !== null && !isLoading) {
                    fetchProducts();
                }
            });
        }, {
            rootMargin: '160px 0px',
        });

        observer.observe(sentinel);
    }

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

    loading.classList.toggle('hidden', nextPage === null);
    loading.classList.toggle('flex', nextPage !== null);
})();
</script>
@endpush
