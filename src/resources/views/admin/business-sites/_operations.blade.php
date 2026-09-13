    <section class="space-y-3">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">Sejarah sesi operasi</h3>
            <p class="text-sm text-slate-500">Klik sesi untuk semak transaksi dan kehadiran ejen.</p>
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Business site</th>
                            <th class="px-4 py-3">Business time</th>
                            <th class="px-4 py-3 text-right">Agents</th>
                            <th class="px-4 py-3 text-right">Net sales</th>
                            <th class="px-4 py-3 text-right">Items sold</th>
                            <th class="px-4 py-3 text-right">Net company</th>
                            <th class="px-4 py-3 text-right">Capital</th>
                            <th class="px-4 py-3 text-right">Gross profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($operationSummaries as $summary)
                            <tr
                                class="cursor-pointer transition hover:bg-blue-50/70"
                                data-clickable-operation-row
                                data-href="{{ route('admin.business-site-operations.show', $summary) }}"
                            >
                                <td class="px-4 py-4">
                                    @adminRoute('admin.business-site-operations.show')
<a href="{{ route('admin.business-site-operations.show', $summary) }}" class="font-semibold text-slate-900 outline-none hover:text-[#1a73e8] hover:underline focus-visible:text-[#1a73e8] focus-visible:underline">{{ $summary->businessSite->site_name }}</a>
@endadminRoute
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $summary->businessSite->city }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-700">
                                    <p>{{ $summary->opened_at->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }}</p>
                                    @if ($summary->closed_at)
                                        <p class="mt-1">{{ $summary->closed_at->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }}</p>
                                    @else
                                        <span class="site-live">Sedang Beroperasi</span>
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
                                <td class="px-4 py-4 text-right font-semibold text-blue-700">RM {{ number_format((float) $summary->net_company_total, 2) }}</td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-800">RM {{ number_format((float) $summary->capital_total, 2) }}</td>
                                <td class="px-4 py-4 text-right font-semibold text-emerald-700">RM {{ number_format((float) $summary->gross_profit_total, 2) }}</td>
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

