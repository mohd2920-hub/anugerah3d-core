<div data-correction-item class="grid items-end gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-4">
    <label class="text-xs font-semibold">Product<select required name="items[{{ $index }}][product_id]" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Select product</option>@foreach ($products as $product)<option value="{{ $product->id }}" @selected((string) $item['product_id'] === (string) $product->id)>{{ $product->prd_name }} ({{ $product->prd_code }})</option>@endforeach</select></label>
    <label class="text-xs font-semibold">Quantity<input required type="number" min="1" max="9999" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
    <label class="text-xs font-semibold">Customer discount (RM, entire line)<input type="number" min="0" step="0.01" name="items[{{ $index }}][discount_amount]" value="{{ $item['discount_amount'] ?? 0 }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
    <button type="button" data-remove-correction-item class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700">Remove</button>
    <input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item['sale_item_id'] ?? '' }}">
    <div data-pos-clicker data-initial='@json($item)' class="hidden space-y-2 rounded-xl bg-white p-3 sm:col-span-4"></div>
</div>
