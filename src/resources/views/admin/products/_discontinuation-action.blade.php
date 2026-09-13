<button type="button" data-discontinuation-open
    data-action="{{ route('admin.products.discontinuation.update', $product) }}"
    data-name="{{ $product->prd_name }}" data-code="{{ $product->prd_code }}"
    data-balance="{{ $product->prd_balance }}" data-discontinued="{{ ($product->discontinued_at ?? null) ? 1 : 0 }}"
    aria-haspopup="dialog" aria-controls="product-discontinuation-dialog"
    class="block w-full rounded-md px-3 py-2 text-left text-sm font-semibold text-amber-800 transition hover:bg-amber-50">
    {{ ($product->discontinued_at ?? null) ? 'Aktifkan Semula Produk' : 'Hentikan Produk' }}
</button>
