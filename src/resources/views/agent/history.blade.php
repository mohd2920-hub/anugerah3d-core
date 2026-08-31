@extends('agent.layouts.app')
@section('title', 'Order History | Anugerah3D Agent')
@section('page_title', 'History')

@section('content')
<div class="space-y-5">
    <section class="rounded-[1.75rem] bg-[linear-gradient(145deg,#17324d,#285875)] p-5 text-white shadow-xl shadow-slate-900/10">
        <div class="flex items-center justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-orange-300">Order records</p><p class="mt-2 text-3xl font-black">{{ count($orders) }}</p><p class="mt-1 text-sm text-slate-300">Orders placed</p></div><span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-orange-300"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6M12 7v5l3 2"/></svg></span></div>
        <a href="{{ route('agent.orders.create') }}" class="mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-[#e7682b] text-sm font-extrabold text-white shadow-lg shadow-slate-950/15 transition active:scale-[0.99]">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg>
            New order
        </a>
    </section>

    <div class="flex gap-2 overflow-x-auto pb-1" data-history-filters>
        @foreach (['All', 'Pending', 'Processing', 'Completed', 'Cancelled'] as $status)
            <button type="button" data-history-filter="{{ strtolower($status) }}" @class(['flex-none rounded-full px-4 py-2 text-xs font-extrabold transition', 'bg-[#17324d] text-white' => $loop->first, 'border border-slate-200 bg-white text-slate-500' => !$loop->first])>{{ $status }}</button>
        @endforeach
    </div>

    <section class="space-y-3" data-order-list>
        @foreach ($orders as $order)
            @php
                $statusClasses = match ($order['status']) {
                    'Pending' => 'bg-amber-50 text-amber-700',
                    'Processing' => 'bg-blue-50 text-blue-700',
                    'Completed' => 'bg-emerald-50 text-emerald-700',
                    default => 'bg-red-50 text-red-600',
                };
            @endphp
            <article data-order-card data-status="{{ strtolower($order['status']) }}" data-open-order="order-detail-{{ $loop->index }}" tabindex="0" role="button" class="cursor-pointer rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition active:scale-[0.99]" aria-label="View order {{ $order['number'] }} details">
                <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate font-mono text-[11px] font-bold text-[#17324d]">{{ $order['number'] }}</p><p class="mt-1 text-[10px] text-slate-400">{{ $order['date'] }}</p></div><span class="{{ $statusClasses }} flex-none rounded-full px-2.5 py-1 text-[9px] font-extrabold uppercase">{{ $order['status'] }}</span></div>
                <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4"><div><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Items</p><p class="mt-1 text-sm font-extrabold text-slate-700">{{ $order['items'] }} units</p></div><div class="text-right"><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Amount</p><p class="mt-1 text-base font-black text-[#e7682b]">RM {{ number_format($order['amount'], 2) }}</p></div></div>
                <div class="mt-3 flex items-center justify-between"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600">Payment proof: {{ count($order['payment_proofs']) }} image{{ count($order['payment_proofs']) === 1 ? '' : 's' }}</span></div>
                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3"><span class="text-[10px] font-semibold text-slate-400">{{ count($order['products']) }} product {{ Str::plural('type', count($order['products'])) }}</span><span class="inline-flex items-center gap-1 text-xs font-extrabold text-[#e7682b]">View details <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></span></div>
            </article>
        @endforeach
        <div data-history-empty class="{{ count($orders) > 0 ? 'hidden ' : '' }}rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500">No orders in this status.</div>
    </section>

</div>

@foreach ($orders as $order)
    @php
        $detailStatusClasses = match ($order['status']) {
            'Pending' => 'bg-amber-50 text-amber-700',
            'Processing' => 'bg-blue-50 text-blue-700',
            'Completed' => 'bg-emerald-50 text-emerald-700',
            default => 'bg-red-50 text-red-600',
        };
    @endphp
    <div id="order-detail-{{ $loop->index }}" data-order-modal class="fixed inset-0 z-50 hidden justify-center bg-[#f7f9fa] sm:items-center sm:bg-slate-950/45 sm:p-5 sm:backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="order-title-{{ $loop->index }}">
        <div class="flex h-full w-full max-w-xl flex-col overflow-hidden bg-[#f7f9fa] sm:h-[92vh] sm:rounded-[2rem] sm:shadow-2xl">
            <header class="flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3" style="padding-top: max(.75rem, env(safe-area-inset-top));"><button type="button" data-close-order class="grid h-10 w-10 flex-none place-items-center rounded-full bg-slate-100 text-slate-600" aria-label="Back to history"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></button><div class="min-w-0 flex-1"><p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#e7682b]">Transaction details</p><h2 id="order-title-{{ $loop->index }}" class="truncate font-mono text-sm font-extrabold text-[#17324d]">{{ $order['number'] }}</h2></div><span class="{{ $detailStatusClasses }} flex-none rounded-full px-2.5 py-1 text-[9px] font-extrabold uppercase">{{ $order['status'] }}</span></header>

            <div class="flex-1 space-y-4 overflow-y-auto p-4 pb-8">
                <section class="rounded-3xl bg-[#17324d] p-5 text-white shadow-lg"><p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-300">Order amount</p><div class="mt-2 flex items-end justify-between gap-3"><p class="text-3xl font-black">RM {{ number_format($order['amount'], 2) }}</p><p class="pb-1 text-xs text-slate-300">{{ $order['items'] }} units</p></div><p class="mt-3 border-t border-white/10 pt-3 text-xs text-slate-300">Placed {{ $order['date'] }}</p></section>

                <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><h3 class="text-sm font-extrabold text-[#17324d]">Order progress</h3><div class="mt-4 flex items-start">
                    @foreach ($order['timeline'] as $step)
                        <div class="relative flex min-w-0 flex-1 flex-col items-center text-center">@if (!$loop->last)<span @class(['absolute left-1/2 top-2 h-0.5 w-full', 'bg-emerald-400' => $step['complete'], 'bg-slate-200' => !$step['complete']])></span>@endif<span @class(['relative z-10 h-4 w-4 rounded-full border-4', 'border-emerald-100 bg-emerald-600' => $step['complete'], 'border-slate-100 bg-slate-300' => !$step['complete']])></span><p @class(['mt-2 text-[8px] font-bold leading-3', 'text-slate-700' => $step['complete'], 'text-slate-400' => !$step['complete']])>{{ $step['label'] }}</p></div>
                    @endforeach
                </div></section>

                <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><div class="flex items-center justify-between"><h3 class="text-sm font-extrabold text-[#17324d]">Products</h3><span class="text-[10px] font-bold text-slate-400">{{ count($order['products']) }} types</span></div><div class="mt-3 divide-y divide-slate-100">
                    @foreach ($order['products'] as $product)
                        <article class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                            <span class="grid h-10 w-10 flex-none place-items-center rounded-xl bg-slate-100 text-[9px] font-black text-slate-500">{{ $product['quantity'] }}×</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-xs font-extrabold text-[#17324d]">{{ $product['name'] }}</p>
                                    @if($product['preorder'])<span class="flex-none rounded-full bg-orange-50 px-1.5 py-0.5 text-[7px] font-extrabold uppercase text-[#d95419]">Pre-order</span>@endif
                                </div>
                                <p class="mt-1 font-mono text-[9px] text-slate-400">{{ $product['code'] }}</p>
                                @if(($product['clicker_character_count'] ?? 0) > 0)
                                    @php $clickerCharacters = collect(mb_str_split((string) ($product['clicker_characters'] ?? '')))->filter(fn ($character) => trim((string) $character) !== ''); @endphp
                                    <div class="mt-1.5">
                                        <p class="inline-block rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-slate-600">Characters ({{ $product['clicker_character_count'] }})</p>
                                        <div class="mt-1 flex flex-nowrap items-center gap-1 overflow-x-auto pb-0.5">
                                            @forelse($clickerCharacters as $character)
                                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md border border-orange-200 bg-[linear-gradient(180deg,#fff7ed,#ffedd5)] text-[10px] font-black text-[#c2410c] shadow-sm">{{ strtoupper($character) }}</span>
                                            @empty
                                                <span class="text-xs font-semibold text-slate-400">-</span>
                                            @endforelse
                                        </div>
                                        <div class="mt-2 flex gap-2">
                                            @foreach (['Casing' => $product['clicker_casing_image_url'] ?? null, 'Huruf' => $product['clicker_huruf_image_url'] ?? null] as $label => $imageUrl)
                                                @if ($imageUrl)
                                                    <a href="{{ $imageUrl }}" target="_blank" rel="noopener" class="block">
                                                        <span class="mb-1 block text-[8px] font-bold uppercase text-slate-400">{{ $label }}</span>
                                                        <img src="{{ $imageUrl }}" alt="{{ $label }} selected" loading="lazy" class="h-12 w-12 rounded-lg border border-slate-200 object-cover">
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                <p class="mt-1 text-[10px] text-slate-500">{{ $product['quantity'] }} × RM {{ number_format($product['price'], 2) }}</p>
                            </div>
                            <p class="flex-none text-xs font-black text-[#e7682b]">RM {{ number_format($product['quantity'] * $product['price'], 2) }}</p>
                        </article>
                    @endforeach
                </div><div class="mt-4 flex items-end justify-between border-t border-slate-100 pt-4"><span class="text-sm font-bold text-slate-500">Total</span><span class="text-xl font-black text-[#e7682b]">RM {{ number_format($order['amount'], 2) }}</span></div></section>

                <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><h3 class="text-sm font-extrabold text-[#17324d]">Fulfilment & payment</h3><dl class="mt-3 divide-y divide-slate-100"><div class="flex justify-between gap-4 py-3 first:pt-0"><dt class="text-xs text-slate-400">Method</dt><dd class="text-right text-xs font-extrabold text-slate-700">{{ $order['fulfilment'] }}</dd></div><div class="flex justify-between gap-4 py-3"><dt class="text-xs text-slate-400">Payment</dt><dd class="text-right text-xs font-extrabold text-slate-700">{{ $order['payment'] }}</dd></div><div class="py-3"><dt class="text-xs text-slate-400">Recipient</dt><dd class="mt-1 text-xs font-extrabold text-slate-700">{{ $order['recipient'] }} · {{ $order['phone'] }}</dd></div><div class="py-3 last:pb-0"><dt class="text-xs text-slate-400">{{ $order['fulfilment'] === 'Delivery' ? 'Delivery address' : 'Pickup location' }}</dt><dd class="mt-1 text-xs font-semibold leading-5 text-slate-700">{{ $order['address'] }}</dd></div></dl></section>

                <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between"><h3 class="text-sm font-extrabold text-[#17324d]">Payment proof</h3><span class="text-[10px] font-bold text-slate-400">{{ count($order['payment_proofs']) }} image{{ count($order['payment_proofs']) === 1 ? '' : 's' }}</span></div>
                    @if (count($order['payment_proofs']) > 0)
                        <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                            @foreach ($order['payment_proofs'] as $proofUrl)
                                <button type="button" data-proof-preview="{{ $proofUrl }}" class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                    <img src="{{ $proofUrl }}" alt="Payment proof" class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-xs font-semibold text-slate-500">No payment proof uploaded for this order.</p>
                    @endif
                </section>

                @if ($order['notes'])<section class="rounded-3xl border border-amber-200 bg-amber-50 p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Order notes</p><p class="mt-2 text-xs font-semibold leading-5 text-amber-900">{{ $order['notes'] }}</p></section>@endif
            </div>
            <footer class="border-t border-slate-200 bg-white p-4" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));"><button type="button" data-close-order class="h-12 w-full rounded-2xl bg-[#17324d] text-sm font-extrabold text-white">Back to history</button></footer>
        </div>
    </div>
@endforeach

<div class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/80 p-4" data-proof-modal>
    <button type="button" class="absolute inset-0" data-proof-close aria-label="Close payment proof preview"></button>
    <div class="relative w-full max-w-3xl">
        <button type="button" class="absolute -top-12 right-0 grid h-10 w-10 place-items-center rounded-full bg-white/20 text-2xl leading-none text-white" data-proof-close aria-label="Close payment proof preview">×</button>
        <button type="button" class="absolute left-2 top-1/2 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/20 text-2xl leading-none text-white transition hover:bg-white/30" data-proof-prev aria-label="Previous proof">‹</button>
        <button type="button" class="absolute right-2 top-1/2 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/20 text-2xl leading-none text-white transition hover:bg-white/30" data-proof-next aria-label="Next proof">›</button>
        <p class="absolute left-1/2 top-3 z-10 -translate-x-1/2 rounded-full bg-black/30 px-3 py-1 text-xs font-bold text-white" data-proof-counter>1 / 1</p>
        <div class="overflow-hidden rounded-2xl bg-white shadow-2xl">
            <img src="" alt="Payment proof preview" class="max-h-[78vh] w-full bg-slate-100 object-contain" data-proof-modal-src>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-history-filter]').forEach((button) => button.addEventListener('click', () => {
        const filter = button.dataset.historyFilter;
        let visible = 0;
        document.querySelectorAll('[data-history-filter]').forEach((item) => {
            const active = item === button;
            item.classList.toggle('bg-[#17324d]', active); item.classList.toggle('text-white', active); item.classList.toggle('border', !active); item.classList.toggle('border-slate-200', !active); item.classList.toggle('bg-white', !active); item.classList.toggle('text-slate-500', !active);
        });
        document.querySelectorAll('[data-order-card]').forEach((card) => { const show = filter === 'all' || card.dataset.status === filter; card.classList.toggle('hidden', !show); if (show) visible++; });
        document.querySelector('[data-history-empty]').classList.toggle('hidden', visible > 0);
    }));

    const openOrder = (card) => { const modal = document.getElementById(card.dataset.openOrder); modal.classList.remove('hidden'); modal.classList.add('flex'); };
    document.querySelectorAll('[data-open-order]').forEach((card) => {
        card.addEventListener('click', () => openOrder(card));
        card.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openOrder(card); } });
    });
    document.querySelectorAll('[data-close-order]').forEach((button) => button.addEventListener('click', () => { const modal = button.closest('[data-order-modal]'); modal.classList.add('hidden'); modal.classList.remove('flex'); }));

    const proofModal = document.querySelector('[data-proof-modal]');
    const proofModalSrc = document.querySelector('[data-proof-modal-src]');
    const proofCounter = document.querySelector('[data-proof-counter]');
    const proofPrev = document.querySelector('[data-proof-prev]');
    const proofNext = document.querySelector('[data-proof-next]');
    let proofImages = [];
    let proofIndex = 0;
    let touchStartX = null;

    const renderProofPreview = () => {
        if (!proofModalSrc || proofImages.length === 0) return;
        proofModalSrc.src = proofImages[proofIndex] || '';

        if (proofCounter) {
            proofCounter.textContent = `${proofIndex + 1} / ${proofImages.length}`;
        }

        const multiple = proofImages.length > 1;
        proofPrev?.classList.toggle('hidden', !multiple);
        proofNext?.classList.toggle('hidden', !multiple);
    };

    const openProofPreview = (images, index) => {
        if (!proofModal || !proofModalSrc) return;
        proofImages = images;
        proofIndex = index;
        renderProofPreview();
        proofModal.classList.remove('hidden');
        proofModal.classList.add('flex');
    };

    const closeProofPreview = () => {
        if (!proofModal || !proofModalSrc) return;
        proofModal.classList.add('hidden');
        proofModal.classList.remove('flex');
        proofModalSrc.src = '';
        proofImages = [];
        proofIndex = 0;
        touchStartX = null;
    };

    const showPrevProof = () => {
        if (proofImages.length <= 1) return;
        proofIndex = (proofIndex - 1 + proofImages.length) % proofImages.length;
        renderProofPreview();
    };

    const showNextProof = () => {
        if (proofImages.length <= 1) return;
        proofIndex = (proofIndex + 1) % proofImages.length;
        renderProofPreview();
    };

    document.querySelectorAll('[data-proof-preview]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();

            const gallery = Array.from(button.closest('section')?.querySelectorAll('[data-proof-preview]') || []);
            const images = gallery
                .map((item) => item.dataset.proofPreview)
                .filter((src) => typeof src === 'string' && src !== '');
            const index = Math.max(0, images.indexOf(button.dataset.proofPreview || ''));

            openProofPreview(images, index);
        });
    });

    document.querySelectorAll('[data-proof-close]').forEach((button) => {
        button.addEventListener('click', closeProofPreview);
    });

    proofPrev?.addEventListener('click', (event) => {
        event.stopPropagation();
        showPrevProof();
    });

    proofNext?.addEventListener('click', (event) => {
        event.stopPropagation();
        showNextProof();
    });

    document.addEventListener('keydown', (event) => {
        if (!proofModal || proofModal.classList.contains('hidden')) return;
        if (event.key === 'ArrowLeft') showPrevProof();
        if (event.key === 'ArrowRight') showNextProof();
        if (event.key === 'Escape') closeProofPreview();
    });

    proofModalSrc?.addEventListener('touchstart', (event) => {
        touchStartX = event.changedTouches[0]?.clientX ?? null;
    }, { passive: true });

    proofModalSrc?.addEventListener('touchend', (event) => {
        if (touchStartX === null) return;
        const touchEndX = event.changedTouches[0]?.clientX ?? touchStartX;
        const deltaX = touchEndX - touchStartX;
        touchStartX = null;

        if (Math.abs(deltaX) < 40) return;
        if (deltaX > 0) showPrevProof();
        if (deltaX < 0) showNextProof();
    }, { passive: true });
</script>
@endpush
