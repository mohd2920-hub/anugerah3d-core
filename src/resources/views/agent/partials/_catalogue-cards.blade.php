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
