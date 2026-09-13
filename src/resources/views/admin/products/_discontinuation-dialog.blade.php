<dialog id="product-discontinuation-dialog" aria-labelledby="discontinuation-title" aria-describedby="discontinuation-description"
    class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm">
    <form method="POST" class="flex max-h-[90dvh] flex-col" data-discontinuation-form>
        @csrf
        @method('PATCH')
        <input type="hidden" name="discontinued" value="1">
        <header class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Status produk</p>
                <h2 id="discontinuation-title" class="mt-1 text-xl font-bold">Hentikan Produk</h2>
            </div>
            <button type="button" data-discontinuation-close aria-label="Tutup" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-xl text-slate-600 hover:bg-slate-200">×</button>
        </header>
        <div class="min-h-0 space-y-4 overflow-y-auto px-5 py-4">
            <div class="flex items-center justify-between gap-4 rounded-xl bg-slate-50 p-4">
                <div class="min-w-0">
                    <p data-discontinuation-name class="break-words text-base font-bold"></p>
                    <p data-discontinuation-code class="mt-1 break-all text-xs text-slate-500"></p>
                </div>
                <div class="shrink-0 text-right"><p class="text-xs text-slate-500">Baki stok</p><p class="text-2xl font-bold"><span data-discontinuation-balance></span> <span class="text-xs font-medium">unit</span></p></div>
            </div>
            <p id="discontinuation-description" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm leading-6 text-amber-900"></p>
            <div data-discontinuation-reason>
                <label for="discontinuation-reason" class="mb-2 block text-sm font-semibold">Catatan <span class="font-normal text-slate-500">(pilihan)</span></label>
                <textarea id="discontinuation-reason" name="reason" maxlength="500" rows="2" placeholder="Contoh: Pengeluar tidak lagi menghasilkan produk ini" class="w-full resize-none rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-100"></textarea>
            </div>
        </div>
        <footer class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
            <button type="button" data-discontinuation-close class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-100">Batal</button>
            <button type="submit" data-discontinuation-submit class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-50">Sahkan Hentikan Produk</button>
        </footer>
    </form>
</dialog>
