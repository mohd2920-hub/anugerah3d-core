@adminRoute('admin.products.balance.update')
<dialog id="product-balance-dialog" aria-labelledby="balance-title" class="fixed inset-0 m-auto w-[calc(100%_-_2rem)] max-w-lg max-h-[90dvh] overflow-y-auto rounded-2xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60">
    <form class="space-y-4 p-5">
        @csrf
        <h2 id="balance-title" class="text-xl font-bold">Edit Baki Stok</h2>
        <p data-balance-name class="text-sm font-semibold"></p>
        <p data-balance-error role="alert" class="text-sm text-red-700"></p>
        <div data-balance-fields class="space-y-4" hidden>
            <label data-balance-variation-label class="block text-sm font-semibold">Casing / saiz<select data-balance-variation class="mt-1 w-full rounded-lg border border-slate-300 p-2"></select></label>
            <p class="rounded-xl bg-blue-50 p-3 text-sm">Baki semasa: <strong data-balance-current></strong> unit</p>
            <label class="block text-sm font-semibold">Baki baharu<input name="quantity" type="number" required min="0" max="10000000" step="1" class="mt-1 w-full rounded-lg border border-slate-300 p-2"></label>
            <p class="text-xs text-slate-500">Angka ini menggantikan baki semasa bagi produk atau variasi yang dipilih.</p>
            <label class="block text-sm font-semibold">Sebab pelarasan<textarea name="reason" required maxlength="500" rows="2" placeholder="Contoh: kiraan fizikal atau stok rosak" class="mt-1 w-full rounded-lg border border-slate-300 p-2"></textarea></label>
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
            <button type="button" data-balance-close class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Batal</button>
            <button type="submit" data-balance-submit disabled class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">Simpan Stok</button>
        </div>
    </form>
</dialog>
@endadminRoute
