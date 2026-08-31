@props(['product' => null])

@php
    $images = $product?->images ?? collect();
    $removedImageIds = collect(old('remove_image_ids', []))->map(fn ($id) => (int) $id);
    $selectedMain = old('main_image', $images->first() ? 'existing-'.$images->first()->getKey() : null);
@endphp

<section data-product-image-manager class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Product pictures</h2>
            <p class="mt-1 text-xs text-slate-500">Upload up to 5 JPG, PNG, or WebP pictures with a maximum width of 500px. Choose one as the main picture.</p>
        </div>
        <span data-image-count class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm">{{ $images->count() }} of 5</span>
    </div>

    <label class="mt-4 flex cursor-pointer items-center justify-center gap-3 rounded-xl border-2 border-dashed border-slate-300 bg-white px-5 py-6 text-center transition hover:border-[#1a73e8] hover:bg-blue-50">
        <svg class="h-6 w-6 text-[#1a73e8]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4m0 0-4 4m4-4 4 4"/><path d="M4 15v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/></svg>
        <span><span class="block text-sm font-semibold text-slate-800">Choose product pictures</span><span class="mt-1 block text-xs text-slate-500">You can select several pictures together · 5 MB each · max width 500px</span></span>
        <input data-product-image-input type="file" name="product_images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple class="sr-only">
    </label>

    <p data-image-error class="mt-2 hidden text-sm font-medium text-red-600"></p>

    @if ($images->isNotEmpty())
        <div class="mt-5">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Current pictures</p>
            <div data-existing-images class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($images as $image)
                    @php
                        $imageUrl = filter_var($image->image_path, FILTER_VALIDATE_URL) ? $image->image_path : asset(ltrim($image->image_path, '/'));
                        $isRemoved = $removedImageIds->contains($image->getKey());
                    @endphp
                    <article data-existing-image-card data-image-id="{{ $image->getKey() }}" @class(['relative overflow-hidden rounded-xl border bg-white p-2 transition', 'border-[#1a73e8] ring-2 ring-blue-100' => $selectedMain === 'existing-'.$image->getKey() && ! $isRemoved, 'border-slate-200 opacity-40' => $isRemoved])>
                        <div class="relative aspect-square overflow-hidden rounded-lg bg-slate-100">
                            <img src="{{ $imageUrl }}" alt="{{ $image->alt_text ?: $product->prd_name }}" class="h-full w-full object-cover">
                            <span data-main-badge @class(['absolute left-2 top-2 rounded-full bg-[#1a73e8] px-2 py-1 text-[10px] font-bold text-white shadow', 'hidden' => $selectedMain !== 'existing-'.$image->getKey() || $isRemoved])>Main</span>
                        </div>
                        <label class="mt-2 flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-700">
                            <input data-main-image type="radio" name="main_image" value="existing-{{ $image->getKey() }}" class="h-4 w-4 border-slate-300 text-[#1a73e8] focus:ring-[#1a73e8]" @checked($selectedMain === 'existing-'.$image->getKey() && ! $isRemoved) @disabled($isRemoved)>
                            Main picture
                        </label>
                        <input data-remove-existing id="remove-image-{{ $image->getKey() }}" type="checkbox" name="remove_image_ids[]" value="{{ $image->getKey() }}" class="sr-only" @checked($isRemoved)>
                        <label for="remove-image-{{ $image->getKey() }}" data-remove-label class="mt-2 inline-flex cursor-pointer text-xs font-semibold text-red-600">{{ $isRemoved ? 'Undo removal' : 'Remove' }}</label>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    <div data-new-images-section class="mt-5 hidden">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">New pictures</p>
        <div data-new-image-previews class="grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
    </div>

    @error('product_images')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error('product_images.*')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error('main_image')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error('remove_image_ids.*')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</section>
