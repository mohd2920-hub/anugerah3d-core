@props(["type", "images" => collect(), "stockAvailable" => false, "stocks" => []])

@php
    $existingImages = collect($images)->keyBy("position");
    $title = str($type)->headline()->toString();
@endphp

<div {{ $attributes->merge(["class" => "rounded-xl border border-slate-200 bg-white p-4"]) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
            <p class="mt-1 text-xs text-slate-500">Upload satu gambar setiap baris dan masukkan nama. Maksimum 25 gambar.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $existingImages->count() }} / 25</span>
    </div>

    <div class="mt-4 max-h-[38rem] space-y-2 overflow-y-auto pr-1">
        @foreach (range(1, 25) as $position)
            @php
                $image = $existingImages->get($position);
                $inputPrefix = "clicker_images.".$type.".".$position;
                $inputName = "clicker_images[".$type."][".$position."]";
            @endphp

            <div id="clicker-{{ $type }}-{{ $image->id ?? 'new-'.$position }}" data-clicker-slot data-saved-name="{{ $image->alt_text ?? '' }}" class="scroll-mt-24 grid items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2 sm:grid-cols-[2rem_3.5rem_minmax(0,1fr)_minmax(0,1fr)]">
                <span class="text-center text-xs font-bold text-slate-500">{{ $position }}</span>

                <div class="h-14 w-14 overflow-hidden rounded-lg border border-slate-200 bg-white">
                    @if ($image)
                        <img data-slot-preview src="{{ filter_var($image->image_path, FILTER_VALIDATE_URL) ? $image->image_path : asset(ltrim($image->image_path, "/")) }}" alt="{{ $image->alt_text }}" class="h-full w-full object-cover">
                        <input type="hidden" name="{{ $inputName }}[id]" value="{{ $image->id }}">
                    @endif
                    <div data-slot-empty @if($image) hidden @endif class="h-full place-items-center text-center text-[10px] font-semibold text-slate-400">Empty</div>
                </div>

                <div>
                    <label class="sr-only" for="clicker-{{ $type }}-name-{{ $position }}">{{ $title }} image {{ $position }} name</label>
                    <input
                        id="clicker-{{ $type }}-name-{{ $position }}"
                        type="text"
                        name="{{ $inputName }}[name]"
                        value="{{ old($inputPrefix.".name", $image->alt_text ?? "") }}"
                        placeholder="Name, e.g. {{ $title }} A"
                        maxlength="100"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100"
                        @required($image)
                    >
                </div>

                <div class="flex min-w-0 items-center gap-2">
                    <label class="sr-only" for="clicker-{{ $type }}-image-{{ $position }}">{{ $title }} image {{ $position }} file</label>
                    <input
                        id="clicker-{{ $type }}-image-{{ $position }}"
                        type="file"
                        name="{{ $inputName }}[image]"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        class="block min-w-0 w-full text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-[#1a73e8] hover:file:bg-blue-100"
                    >
                    <input type="hidden" data-slot-remove name="{{ $inputName }}[remove]" value="{{ $image ? old($inputPrefix.'.remove', 0) : 0 }}">
                    <button type="button" data-slot-clear title="Kosongkan slot" aria-label="Kosongkan slot {{ $title }} {{ $position }}" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-red-600 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-red-500">
                        <x-heroicon-o-trash class="h-5 w-5" />
                    </button>
                    <button type="button" data-slot-undo hidden title="Batal Padam" aria-label="Batal Padam {{ $title }} {{ $position }}" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-blue-500">
                        <x-heroicon-o-arrow-uturn-left class="h-5 w-5" />
                    </button>
                </div>
                @if ($type === 'casing' && $stockAvailable)
                    <x-admin.clicker-casing-stock-input :position="$position" :casing="$image" :stocks="$stocks[$image->id ?? 0] ?? []" />
                @endif
                <p data-slot-status role="status" class="text-xs text-slate-600 sm:col-span-4"></p>
                @error($inputPrefix.'.remove')
                    <p class="text-xs text-red-600 sm:col-span-4">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>

    @error("clicker_images.".$type)
        <span class="mt-3 block text-sm text-red-600">{{ $message }}</span>
    @enderror
</div>
