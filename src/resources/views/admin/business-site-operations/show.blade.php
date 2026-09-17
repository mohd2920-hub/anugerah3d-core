@extends('admin.layouts.app')

@section('title', $operation->businessSite->site_name.' Operation | Anugerah3D Admin')
@section('page_title', 'Business Site Details')

@section('content')
<div class="space-y-6">
    @adminRoute('admin.sales.create')
<a href="{{ route('admin.sales.create', $operation) }}" class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Add Missing Sale</a>
@endadminRoute
    @error('business_site_operation')
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div>
        @adminRoute('admin.business-sites.summary')
<a href="{{ route('admin.business-sites.summary', ['businessSite' => $operation->businessSite, 'period' => 'date', 'date' => $operation->opened_at->copy()->timezone('Asia/Kuala_Lumpur')->toDateString()]) }}" class="text-sm font-semibold text-[#1a73e8]">← Sales summary · {{ $operation->businessSite->site_name }}</a>
@endadminRoute
        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-slate-950">{{ $operation->businessSite->site_name }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $operation->businessSite->city }}</p>
            </div>
            @if ($operation->closed_at)
                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">Closed</span>
            @else
                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">Open now</span>
            @endif
        </div>
        <div class="mt-4 flex flex-wrap gap-x-8 gap-y-2 text-sm text-slate-600">
            <p><span class="font-semibold text-slate-800">Open:</span> {{ $operation->opened_at->format('d M Y, h:i A') }}</p>
            <p><span class="font-semibold text-slate-800">Close:</span> {{ $operation->closed_at?->format('d M Y, h:i A') ?? 'Still open' }}</p>
        </div>
    </div>


    @if(auth('admin')->user()?->isSuperAdmin() && \Illuminate\Support\Facades\Schema::hasTable('operation_closure_corrections'))
        <section class="rounded-xl bg-white p-5 ring-1 ring-slate-200">
            <h2 class="font-semibold">Betulkan Penutupan Sesi</h2>
            <p class="mt-2 text-sm text-slate-500">Superadmin sahaja. Tetapkan masa tamat operasi sebenar. Rekod selepas masa ini memerlukan sesi pengganti. Kehadiran asal dikekalkan.</p>
            @if($errors->any())
                <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">
                    <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.business-site-operations.closure-preview', $operation) }}" class="mt-4 space-y-3">
                @csrf
                <label class="block text-sm">Masa tutup sebenar<input type="datetime-local" step="1" name="closed_at" required value="{{ old('closed_at', $operation->closed_at?->format('Y-m-d\TH:i:s')) }}" class="mt-1 block w-full rounded border-slate-300"></label>
                <label class="block text-sm">Masa buka sesi pengganti (jika ada rekod selepasnya)<input type="datetime-local" step="1" name="next_opened_at" value="{{ old('next_opened_at') }}" class="mt-1 block w-full rounded border-slate-300"></label>
                <label class="block text-sm">Tarikh laporan sesi pengganti<input type="date" name="next_report_date" value="{{ old('next_report_date') }}" class="mt-1 block w-full rounded border-slate-300"></label>
                <label class="block text-sm">Sebab pembetulan<textarea name="reason" required maxlength="2000" class="mt-1 block w-full rounded border-slate-300">{{ old('reason') }}</textarea></label>
                <button class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Semak Pembetulan</button>
            </form>
            @if($closureCorrections->isNotEmpty())
                <h3 class="mt-5 font-semibold">Sejarah pembetulan</h3>
                @foreach($closureCorrections as $correction)
                    @php
                        $beforeClosure = json_decode($correction->before_snapshot, true);
                        $afterClosure = json_decode($correction->after_snapshot, true);
                    @endphp
                    <p class="mt-2 text-sm">{{ $correction->created_at }} · {{ $correction->admin_name }} · {{ $correction->reason }}</p>
                    <p class="text-xs text-slate-500">Tutup: {{ $beforeClosure['operation']['closed_at'] ?? 'Terbuka' }} → {{ $afterClosure['operation']['closed_at'] }} · Sesi pengganti: {{ $correction->replacement_operation_id ?? 'Tiada' }} · {{ count($afterClosure['moved_sale_ids']) }} jualan dipindahkan</p>
                @endforeach
            @endif
        </section>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Net sales</p>
            <p class="mt-3 text-3xl font-bold text-[#1a73e8]">RM {{ number_format($summary['sales_total'], 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ number_format($summary['sales_count']) }} receipt(s)</p>
        </article>
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Net company</p>
            <p class="mt-3 text-3xl font-bold text-blue-700">RM {{ number_format($summary['net_company_total'], 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">Full net sales retained by company</p>
        </article>
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Capital</p>
            <p class="mt-3 text-3xl font-bold text-slate-950">RM {{ number_format($summary['capital_total'], 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">Product cost multiplied by units sold</p>
        </article>
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Gross profit</p>
            <p class="mt-3 text-3xl font-bold text-emerald-700">RM {{ number_format($summary['gross_profit_total'], 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">Net company minus capital</p>
        </article>
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Items sold</p>
            <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($summary['items_sold']) }}</p>
            <p class="mt-1 text-sm text-slate-500">Total product units sold</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-semibold text-slate-950">Agent attendance</h3>
            <p class="mt-1 text-sm text-slate-500">Agents who checked in during this business session.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Agent</th><th class="px-5 py-3">Check in</th><th class="px-5 py-3">Check out</th><th class="px-5 py-3 text-right">Total time</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($attendances as $attendance)
                        @php
                            $attendanceStart = $attendance->signed_in_at->max($operation->opened_at);
                            $attendanceEnd = $attendance->signed_out_at;
                            if ($operation->closed_at && (! $attendanceEnd || $attendanceEnd->greaterThan($operation->closed_at))) {
                                $attendanceEnd = $operation->closed_at;
                            }
                        @endphp
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $attendance->agent?->agt_name ?? 'Ejen tidak tersedia' }}</p><p class="mt-0.5 font-mono text-xs text-slate-500">{{ $attendance->agent?->login_id ?? '—' }}</p></td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $attendanceStart->format('d M Y, h:i A') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $attendanceEnd?->format('d M Y, h:i A') ?? 'Still checked in' }}</td>
                            <td class="px-5 py-4 text-right"><span class="font-mono font-semibold text-slate-800" data-attendance-timer data-signed-in-at="{{ $attendanceStart->toIso8601String() }}" data-signed-out-at="{{ $attendanceEnd?->toIso8601String() }}">00:00:00</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No agents checked in during this session.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($attendances->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $attendances->links() }}</div>@endif
    </section>

    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-semibold text-slate-950">Sales and receipts</h3>
            <p class="mt-1 text-sm text-slate-500">All sales recorded during this business session.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Receipt</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Sales agent</th><th class="px-5 py-3 text-right">Items</th><th class="px-5 py-3 text-right">Total</th><th class="px-5 py-3 text-right">Action</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="px-5 py-4 font-mono font-semibold text-slate-900">{{ $sale->sale_number }} @if ($sale->voided_at)<span class="rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Void</span>@endif @if ($sale->correction_version > 0)<span class="rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Corrected</span>@endif</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $sale->sold_at->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-4"><p class="font-semibold text-slate-800">{{ $sale->salesAgent?->agt_name ?? 'Ejen tidak tersedia' }}</p><p class="mt-0.5 text-xs text-slate-500">Recorded by {{ $sale->recordedBy?->agt_name ?? 'Admin / rekod asal tidak tersedia' }}</p></td>
                            <td class="px-5 py-4 text-right text-slate-700">{{ number_format((int) $sale->items_sold) }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-slate-900">RM {{ number_format((float) $sale->total_amount, 2) }}</td>
                            <td class="px-5 py-4 text-right">@adminRoute('admin.sales.show')
<a href="{{ route('admin.sales.show', $sale) }}" class="inline-flex rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-[#1a73e8] hover:bg-blue-50">Details</a>
@endadminRoute</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No sales recorded during this session.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sales->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $sales->links() }}</div>@endif
    </section>
    <p class="border-t border-slate-200 pt-6 text-sm text-slate-500">Business sessions are retained for history and cannot be deleted.</p>

</div>
@endsection
