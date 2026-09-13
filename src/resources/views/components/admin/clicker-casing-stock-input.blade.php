@props(['position', 'casing' => null, 'stocks' => []])

<div class="min-w-0 rounded-lg border border-blue-100 bg-blue-50/50 p-3 sm:col-span-4" data-casing-stock-row="{{ $casing->id ?? 'new-'.$position }}">
    <p class="mb-2 text-xs font-semibold text-slate-600">Stok mengikut bilangan huruf</p>
    <div class="grid grid-cols-4 gap-2 lg:grid-cols-8">
        @foreach (range(1, 8) as $count)
            <label class="min-w-0 text-center text-xs font-semibold text-slate-700">
                <span class="mb-1 block">{{ $count }}<span class="sr-only"> huruf</span></span>
                <input type="number" min="0" max="10000000" step="1" required
                    name="clicker_images[casing][{{ $position }}][stock][{{ $count }}]"
                    data-casing-size="{{ $count }}" value="{{ old('clicker_images.casing.'.$position.'.stock.'.$count, $stocks[$count] ?? 0) }}"
                    class="block w-full min-w-0 rounded-lg border border-slate-300 bg-white px-1 py-2 text-center text-sm tabular-nums text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                <input type="hidden" name="clicker_images[casing][{{ $position }}][stock_expected][{{ $count }}]" value="{{ old('clicker_images.casing.'.$position.'.stock_expected.'.$count, $stocks[$count] ?? 0) }}">
            </label>
        @endforeach
    </div>
</div>
