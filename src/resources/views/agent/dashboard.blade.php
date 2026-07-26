@extends('agent.layouts.app')
@section('title', 'Dashboard | Anugerah3D Agent')
@section('page_title', 'Dashboard')

@section('content')
<div class="space-y-6">
    @php
        $catalogueMessage = 'Hi, I am browsing the Anugerah3D catalogue and would like some help choosing a product.';
        $catalogueWhatsappUrl = $agent->whatsappUrl($catalogueMessage);
    @endphp
    <section class="relative overflow-hidden rounded-[1.75rem] bg-[linear-gradient(145deg,#17324d,#285875)] p-5 text-white shadow-xl shadow-slate-900/10">
        <div class="absolute -right-10 -top-12 h-44 w-44 rounded-full bg-[#e7682b]/30 blur-2xl"></div>
        <div class="absolute -bottom-16 -left-12 h-40 w-40 rounded-full bg-cyan-300/10 blur-2xl"></div>
        <div class="relative">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-orange-300">Welcome to Anugerah3D</p>
            <h2 class="mt-3 max-w-sm text-2xl font-black leading-tight tracking-tight">Personalised ideas, made real with 3D printing.</h2>
            <p class="mt-3 max-w-md text-sm leading-6 text-slate-300">Browse ready-stock gifts, practical accessories and custom pieces. Pre-order is available for selected designs.</p>

            <div class="mt-5 overflow-hidden rounded-2xl border border-white/10 bg-white/5 shadow-lg">
                <img src="{{ asset('images/catalogue-hero-3d.jpeg') }}" alt="A colourful collection of personalised 3D-printed gifts and accessories" class="aspect-[16/7] w-full object-cover" loading="eager">
            </div>

            <div class="mt-5 flex flex-wrap gap-2 text-[10px] font-bold text-slate-200">
                <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5">Ready stock</span>
                <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5">Pre-order</span>
                <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5">Custom-made</span>
            </div>

            <div class="mt-5 flex gap-3">
                <a href="#catalogue" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-[#e7682b] px-4 text-xs font-extrabold text-white shadow-lg shadow-slate-950/15">Browse catalogue</a>
                @if ($catalogueWhatsappUrl)
                    <a href="{{ $catalogueWhatsappUrl }}" target="_blank" rel="noopener" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 text-xs font-extrabold text-white backdrop-blur">Ask us</a>
                @endif
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-[1.75rem] border border-orange-200 bg-[linear-gradient(135deg,#fff7ed,#fffbeb)] p-5 shadow-sm">
        <div class="flex items-start gap-4">
            <span class="grid h-12 w-12 flex-none place-items-center rounded-2xl bg-[#e7682b] text-white shadow-md shadow-orange-600/20">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M19 8v6m3-3h-6"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#c6531e]">Agent opportunity</p>
                <h2 class="mt-1 text-lg font-extrabold text-[#17324d]">Interested to join as agent?</h2>
                <a href="{{ $agent->referralUrl() }}" class="mt-4 inline-flex h-11 items-center justify-center rounded-xl bg-[#17324d] px-5 text-xs font-extrabold text-white shadow-sm">Register as agent</a>
            </div>
        </div>
    </section>
    @if ($topProducts->isNotEmpty())
        <section class="space-y-3">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#e7682b]">Hot picks</p>
                    <h2 class="mt-1 text-lg font-extrabold text-[#17324d]">Top products</h2>
                </div>
                <a href="#catalogue" class="text-xs font-bold text-slate-500">View all</a>
            </div>

            <div class="-mx-4 flex gap-3 overflow-x-auto px-4 pb-2 sm:-mx-5 sm:px-5">
                @foreach ($topProducts as $product)
                    @php
                        $topImagePath = $product->images->first()?->image_path ?: $product->prd_picture;
                        $topImageUrl = $topImagePath
                            ? (filter_var($topImagePath, FILTER_VALIDATE_URL) ? $topImagePath : asset(ltrim($topImagePath, '/')))
                            : null;
                    @endphp
                    <a href="#catalogue-product-{{ $product->id }}" class="w-32 flex-none overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition active:scale-[0.98]">
                        <div class="relative aspect-square bg-slate-100">
                            @if ($topImageUrl)
                                <img src="{{ $topImageUrl }}" alt="{{ $product->prd_name }}" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="grid h-full place-items-center text-slate-300"><svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m4 16 4-4 4 4 3-3 5 5"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg></span>
                            @endif
                            <span class="absolute left-2 top-2 rounded-full bg-[#e7682b] px-2 py-1 text-[8px] font-black uppercase tracking-wider text-white shadow-sm">Hot</span>
                        </div>
                        <div class="p-2.5">
                            <p class="line-clamp-2 min-h-8 text-[11px] font-extrabold leading-4 text-[#17324d]">{{ $product->prd_name }}</p>
                            <p class="mt-1.5 text-xs font-black text-[#e7682b]">RM {{ number_format((float) $product->price_selling, 2) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif


    @include('agent.partials.product-catalogue', [
        'products' => $catalogueProducts->getCollection(),
        'catalogueTotal' => $catalogueProducts->total(),
        'catalogueHasMore' => $catalogueProducts->hasMorePages(),
    ])
</div>
@endsection
