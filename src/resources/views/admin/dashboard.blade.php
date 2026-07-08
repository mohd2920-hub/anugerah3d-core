@extends('admin.layouts.app')

@section('title', 'Admin Dashboard | Anugerah3D')

@section('page_title', 'Dashboard')

@section('content')
    <div class="mx-auto max-w-7xl">
        <section class="overflow-hidden rounded-lg bg-[linear-gradient(135deg,#111827_0%,#172554_52%,#312e81_100%)] text-white shadow-xl shadow-blue-950/20">
            <div class="h-1 bg-[linear-gradient(90deg,#4285f4_0%,#a142f4_34%,#fbbc04_67%,#34a853_100%)]"></div>
            <div class="grid gap-8 p-5 sm:p-6 lg:grid-cols-[1fr_360px] lg:p-8">
                <div class="max-w-3xl">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <span class="inline-flex min-h-10 cursor-not-allowed select-none items-center justify-center rounded-lg border border-white/10 bg-white/10 px-4 text-sm font-semibold text-white/55">
                            Create Quote
                        </span>
                        <a href="{{ route('admin.login') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-white/20 bg-white/10 px-4 text-sm font-semibold text-white transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:ring-offset-2 focus:ring-offset-[#172554]">
                            Back to Login
                        </a>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="rounded-lg border border-white/15 bg-white/[0.08] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-100/75">Revenue</p>
                        <p class="mt-2 text-2xl font-semibold">RM 4.8k</p>
                    </div>
                    <div class="rounded-lg border border-white/15 bg-white/[0.08] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-100/75">Queue Health</p>
                        <p class="mt-2 text-2xl font-semibold">On Track</p>
                    </div>
                    <div class="rounded-lg border border-white/15 bg-white/[0.08] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-100/75">Support</p>
                        <p class="mt-2 text-2xl font-semibold">4 Open</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4" aria-label="Admin metrics">
            @foreach ($metrics as $metric)
                <article @class([
                    'overflow-hidden rounded-lg border bg-white shadow-sm shadow-blue-950/[0.04]',
                    'border-blue-200' => $loop->iteration === 1,
                    'border-purple-200' => $loop->iteration === 2,
                    'border-amber-200' => $loop->iteration === 3,
                    'border-emerald-200' => $loop->iteration === 4,
                ])>
                    <div @class([
                        'h-1',
                        'bg-[#4285f4]' => $loop->iteration === 1,
                        'bg-[#a142f4]' => $loop->iteration === 2,
                        'bg-[#fbbc04]' => $loop->iteration === 3,
                        'bg-[#34a853]' => $loop->iteration === 4,
                    ])></div>
                    <div class="p-5">
                        <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                        <div class="mt-4 flex items-end justify-between gap-4">
                            <p class="text-3xl font-semibold text-slate-950">{{ $metric['value'] }}</p>
                            <p @class([
                                'rounded-lg px-2.5 py-1 text-xs font-semibold',
                                'bg-blue-50 text-blue-700' => $loop->iteration === 1,
                                'bg-purple-50 text-purple-700' => $loop->iteration === 2,
                                'bg-amber-50 text-amber-800' => $loop->iteration === 3,
                                'bg-emerald-50 text-emerald-700' => $loop->iteration === 4,
                            ])>{{ $metric['trend'] }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-950/[0.04]">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#5f2eea]">Revenue Trend</p>
                        <h3 class="mt-2 text-base font-semibold text-slate-950">Sales Performance</h3>
                    </div>
                    <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">+18.4%</span>
                </div>

                <div class="mt-6 h-64 overflow-hidden rounded-lg bg-[#f8fafd] p-4">
                    <svg viewBox="0 0 720 240" role="img" aria-label="Sample revenue line chart" class="h-full w-full">
                        <defs>
                            <linearGradient id="revenueFill" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="#4285f4" stop-opacity="0.22" />
                                <stop offset="100%" stop-color="#4285f4" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <g stroke="#dbe5f5" stroke-width="1">
                            <line x1="40" x2="700" y1="40" y2="40" />
                            <line x1="40" x2="700" y1="90" y2="90" />
                            <line x1="40" x2="700" y1="140" y2="140" />
                            <line x1="40" x2="700" y1="190" y2="190" />
                        </g>
                        <path d="M40 185 L120 156 L200 170 L280 122 L360 135 L440 88 L520 104 L600 62 L700 74 L700 214 L40 214 Z" fill="url(#revenueFill)" />
                        <polyline points="40,185 120,156 200,170 280,122 360,135 440,88 520,104 600,62 700,74" fill="none" stroke="#4285f4" stroke-linecap="round" stroke-linejoin="round" stroke-width="5" />
                        <polyline points="40,198 120,180 200,150 280,160 360,118 440,128 520,82 600,96 700,54" fill="none" stroke="#a142f4" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" stroke-dasharray="8 10" />
                        <g fill="#334155" font-size="13" font-weight="600">
                            <text x="35" y="232">Jan</text>
                            <text x="115" y="232">Feb</text>
                            <text x="195" y="232">Mar</text>
                            <text x="275" y="232">Apr</text>
                            <text x="355" y="232">May</text>
                            <text x="435" y="232">Jun</text>
                            <text x="515" y="232">Jul</text>
                            <text x="595" y="232">Aug</text>
                            <text x="685" y="232">Sep</text>
                        </g>
                    </svg>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-blue-700">Revenue</p>
                        <p class="mt-1 text-lg font-semibold text-slate-950">RM 42.6k</p>
                    </div>
                    <div class="rounded-lg border border-purple-100 bg-purple-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-purple-700">Average Order</p>
                        <p class="mt-1 text-lg font-semibold text-slate-950">RM 178</p>
                    </div>
                    <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Close Rate</p>
                        <p class="mt-1 text-lg font-semibold text-slate-950">64%</p>
                    </div>
                </div>
            </article>

            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-950/[0.04]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#5f2eea]">Production Progress</p>
                        <h3 class="mt-2 text-base font-semibold text-slate-950">Workflow Health</h3>
                    </div>
                    <span class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Good</span>
                </div>

                <div class="mt-6 grid gap-5">
                    @foreach ($productionStages as $stage)
                        <div>
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <p class="font-semibold text-slate-800">{{ $stage['label'] }}</p>
                                <p class="text-slate-500">{{ $stage['percent'] }}%</p>
                            </div>
                            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div @class([
                                    'h-full rounded-full',
                                    'bg-[#4285f4]' => $loop->iteration === 1,
                                    'bg-[#a142f4]' => $loop->iteration === 2,
                                    'bg-[#fbbc04]' => $loop->iteration === 3,
                                    'bg-[#34a853]' => $loop->iteration === 4,
                                ]) style="width: {{ $stage['percent'] }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $stage['caption'] }}</p>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
            <article class="rounded-lg border border-slate-200 bg-white shadow-sm shadow-blue-950/[0.04]">
                <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-base font-semibold text-slate-950">Order Pipeline</h3>
                    <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">3 active orders</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="bg-[#f8fafd] text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Order</th>
                                <th class="px-5 py-3">Customer</th>
                                <th class="px-5 py-3">Stage</th>
                                <th class="px-5 py-3">Priority</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr class="transition hover:bg-blue-50/40">
                                <td class="px-5 py-4 font-semibold text-slate-950">A3D-1024</td>
                                <td class="px-5 py-4">Corporate Gift Set</td>
                                <td class="px-5 py-4">Quotation</td>
                                <td class="px-5 py-4"><span class="rounded-lg bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700">Review</span></td>
                            </tr>
                            <tr class="transition hover:bg-blue-50/40">
                                <td class="px-5 py-4 font-semibold text-slate-950">A3D-1023</td>
                                <td class="px-5 py-4">Name Keychain Batch</td>
                                <td class="px-5 py-4">Printing</td>
                                <td class="px-5 py-4"><span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Normal</span></td>
                            </tr>
                            <tr class="transition hover:bg-blue-50/40">
                                <td class="px-5 py-4 font-semibold text-slate-950">A3D-1022</td>
                                <td class="px-5 py-4">Miniature Display</td>
                                <td class="px-5 py-4">Finishing</td>
                                <td class="px-5 py-4"><span class="rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">Urgent</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-950/[0.04]">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="text-base font-semibold text-slate-950">Recent Activity</h3>
                    <span class="rounded-lg bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700">Live</span>
                </div>

                <div class="mt-5 divide-y divide-slate-100">
                    @foreach ($activities as $activity)
                        <div class="flex items-start gap-3 py-4 first:pt-0 last:pb-0">
                            <span @class([
                                'mt-1 h-2.5 w-2.5 rounded-full',
                                'bg-[#4285f4]' => $loop->iteration === 1,
                                'bg-[#a142f4]' => $loop->iteration === 2,
                                'bg-[#fbbc04]' => $loop->iteration === 3,
                                'bg-[#34a853]' => $loop->iteration === 4,
                            ])></span>
                            <p class="text-sm leading-6 text-slate-700">{{ $activity }}</p>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-950/[0.04]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#5f2eea]">Customer List</p>
                        <h3 class="mt-2 text-base font-semibold text-slate-950">Top Customers</h3>
                    </div>
                    <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">4 profiles</span>
                </div>

                <div class="mt-5 divide-y divide-slate-100">
                    @foreach ($customers as $customer)
                        <div class="flex items-center gap-4 py-4 first:pt-0 last:pb-0">
                            <div @class([
                                'grid h-12 w-12 shrink-0 place-items-center rounded-lg text-sm font-bold text-white shadow-sm',
                                'bg-[linear-gradient(135deg,#4285f4,#172554)]' => $loop->iteration === 1,
                                'bg-[linear-gradient(135deg,#a142f4,#312e81)]' => $loop->iteration === 2,
                                'bg-[linear-gradient(135deg,#fbbc04,#b45309)]' => $loop->iteration === 3,
                                'bg-[linear-gradient(135deg,#34a853,#065f46)]' => $loop->iteration === 4,
                            ])>{{ $customer['initials'] }}</div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="truncate font-semibold text-slate-950">{{ $customer['name'] }}</p>
                                    <p class="text-sm font-semibold text-slate-950">{{ $customer['value'] }}</p>
                                </div>
                                <div class="mt-1 flex flex-col gap-1 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                                    <p>{{ $customer['segment'] }} - {{ $customer['lastOrder'] }}</p>
                                    <span @class([
                                        'w-fit rounded-lg px-2.5 py-1 text-xs font-semibold',
                                        'bg-blue-50 text-blue-700' => $loop->iteration === 1,
                                        'bg-purple-50 text-purple-700' => $loop->iteration === 2,
                                        'bg-amber-50 text-amber-800' => $loop->iteration === 3,
                                        'bg-emerald-50 text-emerald-700' => $loop->iteration === 4,
                                    ])>{{ $customer['status'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-950/[0.04]">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#5f2eea]">Channel Mix</p>
                        <h3 class="mt-2 text-base font-semibold text-slate-950">Lead Sources</h3>
                    </div>
                    <p class="text-sm text-slate-500">This month</p>
                </div>

                <div class="mt-6 grid gap-5">
                    @foreach ($channelMix as $channel)
                        <div>
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <p class="font-semibold text-slate-800">{{ $channel['label'] }}</p>
                                <p class="text-slate-500">{{ $channel['percent'] }}%</p>
                            </div>
                            <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100">
                                <div @class([
                                    'h-full rounded-full',
                                    'bg-[#4285f4]' => $loop->iteration === 1,
                                    'bg-[#a142f4]' => $loop->iteration === 2,
                                    'bg-[#34a853]' => $loop->iteration === 3,
                                ]) style="width: {{ $channel['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 rounded-lg bg-[linear-gradient(135deg,#111827_0%,#172554_55%,#312e81_100%)] p-5 text-white">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-100/75">Next action</p>
                    <p class="mt-2 text-lg font-semibold">Follow up 12 pending quotations</p>
                    <p class="mt-2 text-sm leading-6 text-blue-50/80">Prioritize corporate and bulk gift requests before production queue closes.</p>
                </div>
            </article>
        </section>

        <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-950/[0.04]">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-base font-semibold text-slate-950">Admin Modules</h3>
                <p class="text-sm text-slate-500">Core workspace areas</p>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <span class="cursor-not-allowed select-none rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-400" aria-disabled="true">Order management</span>
                <a href="{{ route('admin.products.index') }}" class="rounded-lg border border-slate-200 bg-[#f8fafd] px-4 py-4 text-sm font-semibold text-slate-800 transition hover:border-purple-200 hover:bg-purple-50 hover:text-purple-800">Product catalog</a>
                <span class="cursor-not-allowed select-none rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-400" aria-disabled="true">Customer records</span>
                <span class="cursor-not-allowed select-none rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-400" aria-disabled="true">Agent monitoring</span>
                <a href="{{ route('admin.system.manage-data') }}" class="rounded-lg border border-slate-200 bg-[#f8fafd] px-4 py-4 text-sm font-semibold text-slate-800 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-800">Manage data</a>
            </div>
        </section>
    </div>
@endsection
