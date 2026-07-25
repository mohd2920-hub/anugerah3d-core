@extends('admin.layouts.app')

@section('title', 'Agent Details | Anugerah3D Admin')

@section('page_title', 'Agent Details')

@section('content')
    @php
        $statusClass = match ($agent->agt_status) {
            'pending' => 'bg-blue-100 text-blue-700',
            'new' => 'bg-blue-100 text-blue-700',
            'active' => 'bg-green-100 text-green-700',
            'blocked' => 'bg-red-100 text-red-700',
            'suspended' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-600',
        };

        $profileUrl = $agent->profile_picture
            ? (filter_var($agent->profile_picture, FILTER_VALIDATE_URL) ? $agent->profile_picture : asset($agent->profile_picture))
            : null;

        $whatsappUrl = $agent->whatsappUrl();
        $formatMoney = static fn ($value): string => 'RM '.number_format((float) $value, 2);
    @endphp

    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.agents.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Back to Agents
            </a>

            <a href="{{ route('admin.agents.edit', $agent) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white transition hover:bg-[#1558b0]">
                Edit Agent
            </a>
        </div>

        @if (in_array($agent->agt_status, [\App\Models\Agent::StatusPending, \App\Models\Agent::StatusNew], true))
            <section class="rounded-lg border border-blue-200 bg-blue-50 p-5 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Pending agent registration</p>
                    <h2 class="mt-1 text-xl font-semibold text-blue-950">Review and approve this applicant</h2>
                    <p class="mt-2 text-sm text-blue-800">@if ($agent->agt_status === \App\Models\Agent::StatusPending) The applicant already received an 8-character password by email. Set the commission and approve access. @else Set the commission and create the agent's initial password. @endif</p>
                </div>
                <form method="POST" action="{{ route('admin.agents.approve', $agent) }}" class="mt-5 grid gap-3 md:grid-cols-3">
                    @csrf
                    @method('PATCH')
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-blue-900">Agent commission (%)</span>
                        <input name="commission_percentage" type="number" value="{{ old('commission_percentage') }}" min="0" max="100" step="0.01" placeholder="e.g. 5.00" required class="h-10 w-full rounded-lg border border-blue-200 bg-white px-3 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    </label>
                    @if ($agent->agt_status === \App\Models\Agent::StatusNew)
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-blue-900">Initial password</span>
                        <input name="password" type="password" minlength="8" autocomplete="new-password" required class="h-10 w-full rounded-lg border border-blue-200 bg-white px-3 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-blue-900">Confirm password</span>
                        <input name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required class="h-10 w-full rounded-lg border border-blue-200 bg-white px-3 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    </label>
                    @endif
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-5 text-sm font-semibold text-white hover:bg-[#1558b0] md:col-span-3 md:justify-self-end">{{ $agent->agt_status === \App\Models\Agent::StatusPending ? 'Approve agent' : 'Approve and create login' }}</button>
                </form>
                @error('commission_percentage')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('approval')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </section>
        @endif

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-4">
                    @if ($profileUrl)
                        <div class="relative h-20 w-20 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                            <img src="{{ $profileUrl }}" alt="{{ $agent->agt_name }}" loading="lazy" class="h-full w-full object-cover" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden'); this.nextElementSibling.classList.add('flex');">
                            <div class="hidden h-full w-full items-center justify-center text-sm font-semibold text-slate-400">{{ $agent->initials() }}</div>
                        </div>
                    @else
                        <div class="flex h-20 w-20 items-center justify-center rounded-lg border border-slate-200 bg-blue-50 text-lg font-bold text-[#1a73e8]">{{ $agent->initials() }}</div>
                    @endif

                    <div class="min-w-0">
                        <p class="font-mono text-xs font-semibold text-slate-500">{{ $agent->login_id }}</p>
                        <h2 class="mt-1 text-2xl font-semibold text-slate-950">{{ $agent->agt_name }}</h2>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="inline-flex min-w-20 items-center justify-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                {{ $statusOptions[$agent->agt_status] ?? ucfirst($agent->agt_status) }}
                            </span>
                            <span class="text-sm text-slate-500">IC: {{ $agent->id_number ?: '-' }}</span>
                            <span class="text-sm text-slate-500">
                                Referrer:
                                @if ($agent->referrer)
                                    <a href="{{ route('admin.agents.show', $agent->referrer) }}" class="font-semibold text-[#1a73e8] hover:underline">{{ $agent->referrer->agt_name }}</a>
                                @else
                                    -
                                @endif
                            </span>

                        </div>
                    </div>
                </div>

                <div class="text-sm text-slate-600">
                    <p>Last login: <span class="font-medium text-slate-900">{{ $agent->last_login_at?->format('d M Y h:i A') ?: '-' }}</span></p>
                    <p class="mt-1">IP: <span class="font-medium text-slate-900">{{ $agent->last_login_ip ?: '-' }}</span></p>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending Orders</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary['pending_orders']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Sample data</p>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sales</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $formatMoney($summary['total_sales']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Sample total lifetime sales</p>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Commission</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $formatMoney($summary['commission_amount']) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ number_format((float) $summary['commission_rate'], 2) }}% assigned rate</p>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ranking</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['ranking'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Sample leaderboard position</p>
            </article>
        </section>

        <section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Completed Orders</p>
                <p class="mt-2 text-xl font-semibold text-slate-900">{{ number_format($summary['completed_orders']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Sample data</p>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active Customers</p>
                <p class="mt-2 text-xl font-semibold text-slate-900">{{ number_format($summary['active_customers']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Sample data</p>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Avg. Order Value</p>
                <p class="mt-2 text-xl font-semibold text-slate-900">{{ $formatMoney($summary['average_order_value']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Sample metric</p>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Default Discount</p>
                <p class="mt-2 text-xl font-semibold text-slate-900">{{ number_format((float) $agent->discount_percentage, 1) }}%</p>
                <p class="mt-1 text-xs text-slate-500">Product discount setting</p>
            </article>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Team Bonus</h3>
                    <p class="mt-1 text-sm text-slate-600">Tier 1 ialah referral terus. Tier 2 ialah referral kepada Tier 1.</p>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-right">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Total Bonus Payable</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-800">{{ $formatMoney($teamSummary['total_bonus_payable']) }}</p>
                </div>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                <article class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">Tier 1</p>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700">{{ number_format($teamSummary['tier1_count']) }} members</span>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-md bg-white p-3">
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Orders</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ number_format($teamSummary['tier1_order_count']) }}</dd>
                        </div>
                        <div class="rounded-md bg-white p-3">
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Tier Rate</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ number_format((float) $teamSummary['tier1_rate'], 2) }}%</dd>
                        </div>
                        <div class="rounded-md bg-white p-3">
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Sales Amount</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $formatMoney($teamSummary['tier1_sales_total']) }}</dd>
                        </div>
                        <div class="rounded-md bg-white p-3">
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Bonus Payable</dt>
                            <dd class="mt-1 font-semibold text-emerald-700">{{ $formatMoney($teamSummary['tier1_bonus_payable']) }}</dd>
                        </div>
                    </dl>
                </article>

                <article class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">Tier 2</p>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700">{{ number_format($teamSummary['tier2_count']) }} members</span>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-md bg-white p-3">
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Orders</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ number_format($teamSummary['tier2_order_count']) }}</dd>
                        </div>
                        <div class="rounded-md bg-white p-3">
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Tier Rate</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ number_format((float) $teamSummary['tier2_rate'], 2) }}%</dd>
                        </div>
                        <div class="rounded-md bg-white p-3">
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Sales Amount</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $formatMoney($teamSummary['tier2_sales_total']) }}</dd>
                        </div>
                        <div class="rounded-md bg-white p-3">
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Bonus Payable</dt>
                            <dd class="mt-1 font-semibold text-emerald-700">{{ $formatMoney($teamSummary['tier2_bonus_payable']) }}</dd>
                        </div>
                    </dl>
                </article>
            </div>

            <div class="mt-4">
                <article class="rounded-lg border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-semibold text-slate-800">Team Structure (Tier 1 -> Tier 2)</h4>
                        <span class="text-xs text-slate-500">Click any agent to view details</span>
                    </div>

                    @if ($tier1Agents->isEmpty())
                        <p class="mt-3 rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-xs text-slate-500">No Tier 1 members yet.</p>
                    @else
                        <div class="mt-3 space-y-4">
                            @foreach ($tier1Agents as $tier1Agent)
                                <div class="space-y-2">
                                    <a href="{{ route('admin.agents.show', $tier1Agent) }}" class="flex items-center justify-between gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 transition hover:border-[#1a73e8] hover:bg-blue-50">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ $tier1Agent->agt_name }}</p>
                                            <p class="truncate text-xs text-slate-500">{{ $tier1Agent->login_id }}</p>
                                        </div>
                                        <div class="text-right text-xs">
                                            <p class="font-semibold text-slate-700">{{ number_format((int) ($tier1Agent->completed_orders_count ?? 0)) }} orders</p>
                                            <p class="text-emerald-700">{{ $formatMoney((float) ($tier1Agent->completed_orders_total ?? 0)) }}</p>
                                        </div>
                                    </a>

                                    @php($tier2MembersByTier1 = $tier2ByReferrer->get($tier1Agent->id, collect()))
                                    @if ($tier2MembersByTier1->isNotEmpty())
                                        <div class="space-y-2 border-l-2 border-amber-200 pl-4 ml-3">
                                            @foreach ($tier2MembersByTier1 as $tier2Agent)
                                                <a href="{{ route('admin.agents.show', $tier2Agent) }}" class="flex items-center justify-between gap-3 rounded-md border border-amber-200 bg-amber-50/60 px-3 py-2 transition hover:border-[#1a73e8] hover:bg-blue-50">
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $tier2Agent->agt_name }}</p>
                                                        <p class="truncate text-xs text-slate-500">{{ $tier2Agent->login_id }}</p>
                                                    </div>
                                                    <div class="text-right text-xs">
                                                        <p class="font-semibold text-slate-700">{{ number_format((int) ($tier2Agent->completed_orders_count ?? 0)) }} orders</p>
                                                        <p class="text-emerald-700">{{ $formatMoney((float) ($tier2Agent->completed_orders_total ?? 0)) }}</p>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            </div>
        </section>

        <section class="grid gap-3 lg:grid-cols-2">
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Contact</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div>
                        <dt class="text-slate-500">Email</dt>
                        <dd class="font-medium text-slate-900">{{ $agent->email ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Phone</dt>
                        <dd class="font-medium text-slate-900">
                            @if ($whatsappUrl)
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="text-green-700 hover:underline">{{ $agent->phone_number }}</a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                </dl>
            </article>

            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Location</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div>
                        <dt class="text-slate-500">Address</dt>
                        <dd class="font-medium text-slate-900">{{ $agent->address ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">City / State</dt>
                        <dd class="font-medium text-slate-900">{{ $agent->city ?: '-' }} / {{ $agent->state ?: '-' }}</dd>
                    </div>
                </dl>
            </article>
        </section>
    </div>
@endsection
