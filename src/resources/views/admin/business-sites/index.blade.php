@extends('admin.layouts.app')

@section('title', 'Business Sites | Anugerah3D Admin')
@section('page_title', 'Business Site Control')

@section('content')
<div class="space-y-5" data-business-sites-root>
    @include('admin.business-sites._navigation', ['active' => 'control'])
    <section class="site-hero">
        <div><p class="site-eyebrow">OPERATIONS / BUSINESS SITES</p><h2>Kawalan lokasi. Satu pusat.</h2><p>Urus sesi operasi dan kehadiran. Buka ringkasan khusus untuk menyemak prestasi setiap lokasi.</p></div>
        <span class="site-hero-tag">01 / CONTROL</span>
    </section>
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @error('business_site')
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="flex items-end justify-between gap-4 pt-3">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">Business site controls</h3>
            <p class="text-sm text-slate-500">Start, stop and monitor attendance for each site.</p>
        </div>
        @adminRoute('admin.business-sites.create')
<a href="{{ route('admin.business-sites.create') }}" class="rounded-lg bg-[#1a73e8] px-4 py-2.5 text-sm font-semibold text-white">Add new site</a>
@endadminRoute
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3" data-business-site-cards>
        @forelse ($businessSites as $site)
            <article class="site-control-card relative flex min-h-52 flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" data-business-site-card>
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Business site</p>
                        <h4 class="mt-1 text-lg font-semibold text-slate-900">{{ $site->site_name }}</h4>
                        <p class="mt-1 text-xs text-slate-500">{{ $site->city }}</p>
                    </div>
                    @adminRoute('admin.business-sites.show')
<a href="{{ route('admin.business-sites.show', $site) }}" class="site-details-link" aria-label="View details for {{ $site->site_name }}" title="Buka butiran lokasi"></a>
@endadminRoute
                </div>

                <div class="mt-6 flex items-end justify-between gap-4">
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Business status</p>
                        <p class="mb-2 text-xs font-semibold {{ $site->isOpen() ? 'text-emerald-700' : 'text-slate-500' }}">{{ $site->isOpen() ? 'Sedang Beroperasi' : 'Ditutup' }}</p>
                        @if ($site->isOpen())
                            @adminRoute('admin.business-sites.stop')
<button type="button" data-stop-business data-action="{{ route('admin.business-sites.stop', $site) }}" data-site-name="{{ $site->site_name }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 font-mono text-sm font-bold text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-100" title="Click to stop business">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span data-business-timer data-operation-timer data-opened-at="{{ $site->opened_at->toIso8601String() }}">00:00:00</span>
                                <span class="font-sans text-xs">· Tamat</span>
                            </button>
@else
<span class="text-sm font-semibold text-emerald-700">Open now</span>
@endadminRoute
                        @else
                            @adminRoute('admin.business-sites.start')
<form method="POST" action="{{ route('admin.business-sites.start', $site) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-lg bg-[#1a73e8] px-3 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Start business</button>
                            </form>
@endadminRoute
                        @endif
                    </div>

                    <div class="text-right">
                        <p class="text-2xl font-bold text-slate-900">{{ $site->active_pos_sessions_count }}</p>
                        <p class="text-xs font-medium text-slate-500">Active agent(s)</p>
                    </div>
                </div>
                @adminRoute('admin.business-sites.summary')
                <div class="site-card-link"><a class="site-summary-button" href="{{ route('admin.business-sites.summary', $site) }}">Sales summary</a></div>
                @endadminRoute
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center text-sm text-slate-500 sm:col-span-2 xl:col-span-3">No business sites yet.</div>
        @endforelse
    </div>
    {{ $businessSites->withQueryString()->links() }}
    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" data-stop-business-modal role="dialog" aria-modal="true" aria-labelledby="stop-business-title">
        <button type="button" class="absolute inset-0" data-close-stop-business aria-label="Close stop business confirmation"></button>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <p class="text-xs font-bold uppercase tracking-wider text-red-600">Stop business</p>
            <h2 id="stop-business-title" class="mt-1 text-xl font-bold text-slate-900">Stop <span data-stop-business-site></span>?</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">All agents currently checked in at this site will be checked out and POS access will stop immediately.</p>

            <form method="POST" class="mt-6 grid grid-cols-2 gap-3" data-stop-business-form>
                @csrf
                @method('PATCH')
                <button type="button" class="rounded-lg border border-slate-300 px-4 py-2.5 font-semibold text-slate-700" data-close-stop-business>Cancel</button>
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2.5 font-semibold text-white">Stop business</button>
            </form>
        </div>
    </div>
</div>
@endsection
