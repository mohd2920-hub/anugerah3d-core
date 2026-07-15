<div data-checkout-modal class="fixed inset-0 z-50 hidden justify-center bg-[#f7f9fa] sm:items-center sm:bg-slate-950/45 sm:p-5 sm:backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="checkout-title">
    <form data-checkout-form class="flex h-full w-full max-w-xl flex-col overflow-hidden bg-[#f7f9fa] sm:h-[92vh] sm:rounded-[2rem] sm:shadow-2xl">
        <header class="flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3" style="padding-top: max(.75rem, env(safe-area-inset-top));">
            <button type="button" data-back-to-cart class="grid h-10 w-10 flex-none place-items-center rounded-full bg-slate-100 text-slate-600" aria-label="Back to cart"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></button>
            <div class="min-w-0 flex-1"><p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#e7682b]">Final step</p><h2 id="checkout-title" class="text-lg font-extrabold text-[#17324d]">Checkout</h2></div>
            <span class="rounded-full bg-orange-50 px-3 py-1.5 text-[10px] font-extrabold text-[#d95419]">UI preview</span>
        </header>

        <div class="flex-1 space-y-4 overflow-y-auto p-4 pb-8">
            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-extrabold text-[#17324d]">Fulfilment method</h3>
                <div class="mt-3 grid grid-cols-2 gap-2 rounded-2xl bg-slate-100 p-1">
                    <button type="button" data-fulfilment="delivery" class="h-10 rounded-xl bg-white text-xs font-extrabold text-[#17324d] shadow-sm">Delivery</button>
                    <button type="button" data-fulfilment="pickup" class="h-10 rounded-xl text-xs font-extrabold text-slate-500">Self pickup</button>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-extrabold text-[#17324d]">Contact details</h3>
                <div class="mt-3 grid gap-3">
                    <label class="grid gap-1.5 text-xs font-bold text-slate-600">Recipient name<input name="recipient_name" value="{{ $agent->agt_name }}" required class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-medium outline-none focus:border-[#e7682b] focus:ring-3 focus:ring-orange-100"></label>
                    <label class="grid gap-1.5 text-xs font-bold text-slate-600">Phone number<input name="phone_number" value="{{ $agent->phone_number }}" type="tel" required class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-medium outline-none focus:border-[#e7682b] focus:ring-3 focus:ring-orange-100"></label>
                </div>
                <div data-delivery-address class="mt-3">
                    <label class="grid gap-1.5 text-xs font-bold text-slate-600">Delivery address<textarea name="delivery_address" rows="3" required class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium outline-none focus:border-[#e7682b] focus:ring-3 focus:ring-orange-100">{{ collect([$agent->address, $agent->city, $agent->state])->filter()->implode(', ') }}</textarea></label>
                </div>
                <label class="mt-3 grid gap-1.5 text-xs font-bold text-slate-600">Order notes <span class="font-normal text-slate-400">(optional)</span><textarea name="notes" rows="2" placeholder="Colour, delivery or other instructions" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium outline-none focus:border-[#e7682b] focus:ring-3 focus:ring-orange-100"></textarea></label>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-extrabold text-[#17324d]">Payment method</h3>
                <label class="mt-3 flex items-center gap-3 rounded-2xl border-2 border-[#17324d] bg-blue-50/50 p-3"><input name="payment_method" value="bank_transfer" type="radio" checked class="h-4 w-4 text-[#17324d]"><span class="grid h-9 w-9 place-items-center rounded-xl bg-[#17324d] text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 10 9-7 9 7M5 10v8m4-8v8m6-8v8m4-8v8M3 21h18"/></svg></span><span class="min-w-0 flex-1"><span class="block text-sm font-extrabold text-[#17324d]">Bank transfer</span><span class="block text-[10px] text-slate-500">Payment instructions after confirmation</span></span></label>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between"><h3 class="text-sm font-extrabold text-[#17324d]">Order summary</h3><span data-checkout-units class="text-xs font-bold text-slate-500">0 units</span></div>
                <div data-checkout-items class="mt-3 space-y-2"></div>
                <div class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm"><div class="flex justify-between text-slate-500"><span>Products</span><span data-checkout-subtotal class="font-bold text-slate-700">RM 0.00</span></div><div class="flex justify-between text-slate-500"><span>Delivery</span><span class="font-bold text-emerald-600">To be confirmed</span></div><div class="flex items-end justify-between pt-2"><span class="font-extrabold text-[#17324d]">Order total</span><span data-checkout-total class="text-2xl font-black text-[#e7682b]">RM 0.00</span></div></div>
            </section>
        </div>

        <footer class="border-t border-slate-200 bg-white p-4" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));">
            <button type="submit" class="h-13 w-full rounded-2xl bg-[#e7682b] text-sm font-extrabold text-white shadow-lg shadow-orange-600/20">Confirm order · <span data-confirm-total>RM 0.00</span></button>
            <p class="mt-2 text-center text-[10px] text-slate-400">No order will be created during this UI phase.</p>
        </footer>
    </form>
</div>

<div data-checkout-success class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/50 p-5 backdrop-blur-sm" role="dialog" aria-modal="true">
    <div class="w-full max-w-sm rounded-[2rem] bg-white p-6 text-center shadow-2xl"><span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-100 text-emerald-700"><svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg></span><p class="mt-5 text-[10px] font-bold uppercase tracking-[0.14em] text-emerald-700">Checkout preview complete</p><h2 class="mt-2 text-2xl font-black text-[#17324d]">Order details ready</h2><p class="mt-2 text-sm leading-6 text-slate-500">The checkout interface is complete. Backend submission will be connected in the next phase.</p><div class="mt-5 rounded-2xl bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase text-slate-400">Preview total</p><p data-success-total class="mt-1 text-xl font-black text-[#e7682b]">RM 0.00</p></div><button type="button" data-close-success class="mt-5 h-12 w-full rounded-2xl bg-[#17324d] text-sm font-extrabold text-white">Done</button></div>
</div>
