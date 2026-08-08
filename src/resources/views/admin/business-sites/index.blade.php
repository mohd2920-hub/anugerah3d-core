@extends('admin.layouts.app')

@section('title', 'Business Sites | Anugerah3D Admin')
@section('page_title', 'Business Sites')

@section('content')
<div class="space-y-5" data-business-sites-root>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Business sites</h2>
            <p class="text-sm text-slate-500">Open a site for agent attendance and POS access.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @error('business_site')
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <section class="space-y-3">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">Business site sales summary</h3>
            <p class="text-sm text-slate-500">Sales and attendance totals for each business operating session.</p>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Business site</th>
                            <th class="px-4 py-3">Business time</th>
                            <th class="px-4 py-3 text-right">Agents</th>
                            <th class="px-4 py-3 text-right">Sales</th>
                            <th class="px-4 py-3 text-right">Items sold</th>
                            <th class="px-4 py-3 text-right">Commission</th>
                            <th class="px-4 py-3 text-right">Capital</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($operationSummaries as $summary)
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ $summary->businessSite->site_name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $summary->businessSite->city }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-700">
                                    <p>{{ $summary->opened_at->format('d M Y, h:i A') }}</p>
                                    @if ($summary->closed_at)
                                        <p class="mt-1">{{ $summary->closed_at->format('d M Y, h:i A') }}</p>
                                    @else
                                        <button type="button" data-stop-business data-action="{{ route('admin.business-sites.stop', $summary->businessSite) }}" data-site-name="{{ $summary->businessSite->site_name }}" class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100" title="Click to stop business"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Open now</button>
                                    @endif
                                    @if ($summary->closed_at)
                                        <p class="mt-2 text-xs"><strong class="text-slate-900">{{ $summary->opened_at->diffForHumans($summary->closed_at, true) }}</strong></p>
                                    @else
                                        <p class="mt-2"><strong class="font-mono text-sm text-slate-900" data-business-timer data-operation-timer data-opened-at="{{ $summary->opened_at->toIso8601String() }}">00:00:00</strong></p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-800">{{ number_format((int) $summary->agents_count) }}</td>
                                <td class="px-4 py-4 text-right">
                                    <p class="font-semibold text-slate-900">RM {{ number_format((float) $summary->sales_total, 2) }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ number_format((int) $summary->sales_count) }} sale(s)</p>
                                </td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-800">{{ number_format((int) $summary->items_sold) }}</td>
                                <td class="px-4 py-4 text-right font-semibold text-blue-700">RM {{ number_format((float) $summary->commission_total, 2) }}</td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-800">RM {{ number_format((float) $summary->capital_total, 2) }}</td>
                                <td class="px-4 py-4 text-right"><a href="{{ route('admin.business-site-operations.show', $summary) }}" class="inline-flex rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-[#1a73e8] hover:bg-blue-50">Details</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">No business operation records yet. Start a business site to create the first record.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $operationSummaries->withQueryString()->links() }}
    </section>

    <div class="flex items-end justify-between gap-4 pt-3">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">Business site controls</h3>
            <p class="text-sm text-slate-500">Start, stop and monitor attendance for each site.</p>
        </div>
        <a href="{{ route('admin.business-sites.create') }}" class="rounded-lg bg-[#1a73e8] px-4 py-2.5 text-sm font-semibold text-white">Add new site</a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3" data-business-site-cards>
        @forelse ($businessSites as $site)
            <article class="flex min-h-52 flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" data-business-site-card>
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Business site</p>
                        <h4 class="mt-1 truncate text-lg font-semibold text-slate-900">{{ $site->site_name }}</h4>
                    </div>
                    <a href="{{ route('admin.business-sites.show', $site) }}" class="inline-flex h-8 min-w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 px-2 text-sm font-bold tracking-widest text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1a73e8]" aria-label="View details for {{ $site->site_name }}" title="View details">...</a>
                </div>

                <div class="mt-6 flex items-end justify-between gap-4">
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Business status</p>
                        @if ($site->isOpen())
                            <button type="button" data-stop-business data-action="{{ route('admin.business-sites.stop', $site) }}" data-site-name="{{ $site->site_name }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 font-mono text-sm font-bold text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-100" title="Click to stop business">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span data-business-timer data-opened-at="{{ $site->opened_at->toIso8601String() }}">00:00:00</span>
                            </button>
                        @else
                            <form method="POST" action="{{ route('admin.business-sites.start', $site) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-lg bg-[#1a73e8] px-3 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Start business</button>
                            </form>
                        @endif
                    </div>

                    <div class="text-right">
                        <p class="text-2xl font-bold text-slate-900">{{ $site->active_pos_sessions_count }}</p>
                        <p class="text-xs font-medium text-slate-500">Active agent(s)</p>
                    </div>
                </div>
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
