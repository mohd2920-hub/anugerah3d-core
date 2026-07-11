@extends('admin.layouts.app')

@section('title', 'Agent Details | Anugerah3D Admin')

@section('page_title', 'Agent Details')

@section('content')
    @php
        $statusClass = match ($agent->agt_status) {
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
                <p class="mt-1 text-xs text-slate-500">{{ number_format((float) $summary['commission_rate'], 1) }}% sample rate</p>
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
