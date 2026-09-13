@props(["pricing" => [], "disabled" => false, "stocks" => [], "stockAvailable" => false])

@php
    $fields = [
        "price_rm" => ["label" => "Pricing (RM)", "placeholder" => "0.00"],
        "cost_rm" => ["label" => "Cost (RM)", "placeholder" => "0.00"],
        "weight_g" => ["label" => "Weight (g)", "placeholder" => "0.00"],
        "width_mm" => ["label" => "Width (mm)", "placeholder" => "0.00"],
        "height_mm" => ["label" => "Height (mm)", "placeholder" => "0.00"],
        "length_mm" => ["label" => "Length (mm)", "placeholder" => "0.00"],
    ];
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-4">
    <div>
        <h3 class="text-sm font-semibold text-slate-900">Character Pricing</h3>
        <p class="mt-1 text-xs text-slate-500">Setiap baris mempunyai pricing, cost, weight dan dimensions sendiri.</p>
    </div>

    <div class="mt-4 overflow-x-auto">
        <div class="min-w-[980px] space-y-2">
            <div class="grid {{ $stockAvailable ? 'grid-cols-[64px_repeat(6,minmax(130px,1fr))_80px]' : 'grid-cols-[64px_repeat(6,minmax(130px,1fr))]' }} gap-2 px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                @if ($stockAvailable)<span class="order-last">Stock</span>@endif
                <span>Number</span>
                @foreach ($fields as $field)
                    <span>{{ $field["label"] }}</span>
                @endforeach
            </div>

            @foreach (range(1, 8) as $characterCount)
                <div class="grid {{ $stockAvailable ? 'grid-cols-[64px_repeat(6,minmax(130px,1fr))_80px]' : 'grid-cols-[64px_repeat(6,minmax(130px,1fr))]' }} items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2">
                    @if ($stockAvailable)<span data-size-stock-total="{{ $characterCount }}" class="order-last text-center text-sm font-semibold">{{ collect($stocks)->sum(fn ($sizes) => $sizes[$characterCount] ?? 0) }}</span>@endif
                    <span class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700">{{ $characterCount }}</span>

                    @foreach ($fields as $fieldName => $field)
                        <input
                            type="number"
                            name="clicker_character_prices[{{ $characterCount }}][{{ $fieldName }}]"
                            value="{{ old("clicker_character_prices.".$characterCount.".".$fieldName, data_get($pricing, $characterCount.".".$fieldName, "")) }}"
                            aria-label="{{ $characterCount }} character {{ $field["label"] }}"
                            placeholder="{{ $field["placeholder"] }}"
                            step="0.01"
                            min="0"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100"
                            required
                            @disabled($disabled)
                        >
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    @error("clicker_character_prices")
        <span class="mt-3 block text-sm text-red-600">{{ $message }}</span>
    @enderror
</div>
