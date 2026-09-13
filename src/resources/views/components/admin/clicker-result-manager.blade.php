@props(["casingImages" => collect(), "hurufImages" => collect(), "results" => collect(), "product" => null, "stockAvailable" => false, "stocks" => []])

@php
    $casingImages = collect($casingImages);
    $hurufImages = collect($hurufImages);
    $results = collect($results);
    $stockEnabled = $product && \App\Support\CasingStock::enabled($product);
    $stockAvailable = $stockAvailable && $product;
    $renderedCasingIds = [];
    $casingsById = $casingImages->keyBy("id");
    $casingNames = $casingImages->pluck("alt_text", "id");
    $hurufNames = $hurufImages->pluck("alt_text", "id");
    $canAddResult = $casingImages->isNotEmpty() && $hurufImages->isNotEmpty();
@endphp

<div data-casing-stock {{ $attributes->merge(["class" => "rounded-xl border border-slate-200 bg-white p-4"]) }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Result Combination</h3>
            <p class="mt-1 text-xs text-slate-500">Pilih satu casing + satu huruf, kemudian upload satu gambar result. Upload pasangan sama akan menggantikan gambar lama.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $results->count() }} mapped</span>
    </div>

    @if ($results->isNotEmpty())
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($results as $result)
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                    <img src="{{ filter_var($result->image_path, FILTER_VALIDATE_URL) ? $result->image_path : asset(ltrim($result->image_path, "/")) }}" alt="{{ $result->name }}" class="aspect-[4/3] w-full object-cover">
                    <div class="p-3">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $result->name }}</p>
                        <p class="mt-1 truncate text-xs text-slate-500">{{ $casingNames->get($result->casing_image_id, "Casing") }} + {{ $hurufNames->get($result->huruf_image_id, "Huruf") }}</p>
                        @if ($stockAvailable)
                            <div class="mt-3 grid grid-cols-4 gap-2 text-center text-xs lg:grid-cols-8">
                                @foreach (range(1, 8) as $count)
                                    <div class="rounded-lg bg-blue-50 p-2"><span class="block text-slate-500">{{ $count }} huruf</span><strong data-combination-stock="{{ $result->casing_image_id }}:{{ $count }}">{{ $stocks[$result->casing_image_id][$count] ?? 0 }}</strong></div>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-slate-500">Baki dikongsi mengikut casing dan saiz. Laraskan di bahagian Casing.</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <div class="mt-4 grid gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 lg:grid-cols-2">
        <div>
            <label for="clicker-result-casing" class="mb-1 block text-xs font-semibold text-slate-600">Casing</label>
            <select id="clicker-result-casing" name="clicker_result[casing_image_id]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" @disabled(! $canAddResult)>
                <option value="">Select casing</option>
                @foreach ($casingImages as $image)
                    <option value="{{ $image->id }}" @selected((string) old("clicker_result.casing_image_id") === (string) $image->id)>{{ $image->alt_text }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="clicker-result-huruf" class="mb-1 block text-xs font-semibold text-slate-600">Huruf</label>
            <select id="clicker-result-huruf" name="clicker_result[huruf_image_id]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" @disabled(! $canAddResult)>
                <option value="">Select huruf</option>
                @foreach ($hurufImages as $image)
                    <option value="{{ $image->id }}" @selected((string) old("clicker_result.huruf_image_id") === (string) $image->id)>{{ $image->alt_text }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="clicker-result-name" class="mb-1 block text-xs font-semibold text-slate-600">Result name <span class="font-normal text-slate-400">(optional)</span></label>
            <input id="clicker-result-name" type="text" name="clicker_result[name]" value="{{ old("clicker_result.name") }}" maxlength="100" placeholder="Auto: Casing name + Huruf name" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" @disabled(! $canAddResult)>
        </div>

        <div>
            <label for="clicker-result-image" class="mb-1 block text-xs font-semibold text-slate-600">Single result image</label>
            <input id="clicker-result-image" type="file" name="clicker_result[image]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-orange-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-[#e7682b]" @disabled(! $canAddResult)>
        </div>
    </div>

    @unless ($canAddResult)
        <p class="mt-2 text-xs font-medium text-amber-700">Save at least one named casing and one named huruf first.</p>
    @endunless

    @error("clicker_result")
        <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
    @enderror
    @error("clicker_result.image")
        <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
    @enderror
</div>
