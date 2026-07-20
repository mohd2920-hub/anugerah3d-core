@extends('admin.layouts.app')

@section('title', $orders->total().' Orders | Anugerah3D Admin')
@section('page_title', $orders->total().' Orders')

@section('content')
    @php
        $statusClass = static fn (string $status): string => match ($status) {
            'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'processing' => 'bg-blue-50 text-blue-700 ring-blue-200',
            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            default => 'bg-red-50 text-red-700 ring-red-200',
        };
        $paymentClass = static fn (string $status): string => match ($status) {
            'paid' => 'bg-emerald-50 text-emerald-700',
            'refunded' => 'bg-violet-50 text-violet-700',
            default => 'bg-slate-100 text-slate-600',
        };
    @endphp

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-700">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <p class="font-semibold">The order action could not be completed.</p>
                <p class="mt-1">{{ $errors->first() }}</p>
            </div>
        @endif

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Order summary">
            <a href="{{ route('admin.orders.index') }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">All orders</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format($summary['total']) }}</p>
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="rounded-lg border border-amber-200 bg-amber-50 p-4 shadow-sm transition hover:border-amber-400">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Pending action</p>
                <p class="mt-2 text-2xl font-semibold text-amber-900">{{ number_format($summary['pending']) }}</p>
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'processing']) }}" class="rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-sm transition hover:border-blue-400">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Processing</p>
                <p class="mt-2 text-2xl font-semibold text-blue-900">{{ number_format($summary['processing']) }}</p>
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'completed']) }}" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 shadow-sm transition hover:border-emerald-400">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Completed</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-900">{{ number_format($summary['completed']) }}</p>
            </a>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="grid gap-3 lg:grid-cols-[minmax(240px,1fr)_170px_170px_170px_auto]">
                <input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Order no., agent, recipient or product..." class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">

                <select name="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ Str::headline($status) }}</option>
                    @endforeach
                </select>

                <select name="payment_status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    <option value="">All payments</option>
                    @foreach ($paymentStatuses as $paymentStatus)
                        <option value="{{ $paymentStatus }}" @selected($filters['payment_status'] === $paymentStatus)>{{ Str::headline($paymentStatus) }}</option>
                    @endforeach
                </select>

                <select name="fulfilment_method" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    <option value="">All fulfilment</option>
                    <option value="delivery" @selected($filters['fulfilment_method'] === 'delivery')>Delivery</option>
                    <option value="pickup" @selected($filters['fulfilment_method'] === 'pickup')>Self pickup</option>
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white transition hover:bg-[#1558b0]">Filter</button>
                    @if (collect($filters)->filter()->isNotEmpty())
                        <a href="{{ route('admin.orders.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="hidden overflow-visible rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70 md:block">
            <div class="overflow-x-auto">
                <table class="admin-data-table w-full min-w-[1050px] text-xs">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700">Order</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700">Agent / Recipient</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700">Placed</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700">Fulfilment</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700">Payment</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700">Stock</th>
                            <th class="px-3 py-3 text-right font-semibold text-slate-700">Total</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700">Status</th>
                            <th class="px-3 py-3 text-right font-semibold text-slate-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($orders as $order)
                            <tr class="align-top transition hover:bg-slate-50">
                                <td class="px-3 py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-xs font-semibold text-[#1a73e8] hover:underline">{{ $order->order_number }}</a>
                                    <p class="mt-1 text-slate-500">{{ $order->total_units }} units / {{ $order->items_count }} products</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-semibold text-slate-900">{{ $order->agent->agt_name }}</p>
                                    <p class="mt-1 text-slate-500">{{ $order->recipient_name }} · {{ $order->phone_number }}</p>
                                </td>
                                <td class="px-3 py-3 text-slate-600">
                                    <p>{{ $order->placed_at->format('d M Y') }}</p>
                                    <p class="mt-1 text-slate-400">{{ $order->placed_at->format('h:i A') }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-medium text-slate-800">{{ $order->fulfilmentLabel() }}</p>
                                    <p class="mt-1 text-slate-500">{{ $order->paymentMethodLabel() }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="{{ $paymentClass($order->payment_status) }} inline-flex rounded-full px-2.5 py-1 text-[0.68rem] font-semibold">{{ $order->paymentStatusLabel() }}</span>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($order->status === 'cancelled')
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">Released</span>
                                    @elseif ($order->has_stock_shortage)
                                        <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 font-semibold text-red-700 ring-1 ring-red-200">Insufficient</span>
                                    @elseif ($order->status === 'pending')
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700 ring-1 ring-emerald-200">Ready</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 font-semibold text-blue-700">Reserved</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <p class="font-semibold text-slate-900">RM {{ number_format((float) $order->total_amount, 2) }}</p>
                                    <p class="mt-1 text-slate-500">Delivery TBC</p>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="{{ $statusClass($order->status) }} inline-flex rounded-full px-2.5 py-1 text-[0.68rem] font-semibold ring-1">{{ $order->statusLabel() }}</span>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 font-semibold text-slate-700 hover:bg-slate-50">Details</a>
                                        @if ($order->status === 'pending' && ! $order->has_stock_shortage)
                                            <form method="POST" action="{{ route('admin.orders.process', $order) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="inline-flex min-h-9 items-center rounded-lg bg-[#1a73e8] px-3 font-semibold text-white hover:bg-[#1558b0]">Process</button>
                                            </form>
                                        @elseif ($order->status === 'processing')
                                            <form method="POST" action="{{ route('admin.orders.complete', $order) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="inline-flex min-h-9 items-center rounded-lg bg-emerald-600 px-3 font-semibold text-white hover:bg-emerald-700">Complete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-6 py-10 text-center text-slate-600">No orders match the selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-3 md:hidden">
            @forelse ($orders as $order)
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-sm font-semibold text-[#1a73e8]">{{ $order->order_number }}</a>
                            <p class="mt-1 text-xs text-slate-500">{{ $order->placed_at->format('d M Y, h:i A') }}</p>
                        </div>
                        <span class="{{ $statusClass($order->status) }} inline-flex rounded-full px-2.5 py-1 text-[0.68rem] font-semibold ring-1">{{ $order->statusLabel() }}</span>
                    </div>

                    <div class="mt-4">
                        <p class="font-semibold text-slate-900">{{ $order->agent->agt_name }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $order->recipient_name }} · {{ $order->phone_number }}</p>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-xs font-semibold uppercase text-slate-500">Order</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $order->total_units }} units</dd>
                            <dd class="mt-1 text-xs text-slate-500">{{ $order->items_count }} product types</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-xs font-semibold uppercase text-slate-500">Total</dt>
                            <dd class="mt-1 font-semibold text-slate-900">RM {{ number_format((float) $order->total_amount, 2) }}</dd>
                            <dd class="mt-1 text-xs text-slate-500">{{ $order->paymentStatusLabel() }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-xs font-semibold uppercase text-slate-500">Fulfilment</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $order->fulfilmentLabel() }}</dd>
                            <dd class="mt-1 text-xs text-slate-500">{{ $order->paymentMethodLabel() }}</dd>
                        </div>
                        <div @class(['rounded-lg p-3', 'bg-red-50' => $order->has_stock_shortage, 'bg-emerald-50' => ! $order->has_stock_shortage])>
                            <dt class="text-xs font-semibold uppercase text-slate-500">Stock</dt>
                            <dd @class(['mt-1 font-semibold', 'text-red-700' => $order->has_stock_shortage, 'text-emerald-700' => ! $order->has_stock_shortage])>{{ $order->has_stock_shortage ? 'Insufficient' : 'Ready' }}</dd>
                        </div>
                    </dl>

                    <a href="{{ route('admin.orders.show', $order) }}" class="mt-4 inline-flex min-h-10 w-full items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white">View details & actions</a>
                </article>
            @empty
                <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-600 shadow-sm">No orders match the selected filters.</div>
            @endforelse
        </section>

        <div class="flex justify-center">{{ $orders->links('pagination::tailwind') }}</div>
    </div>
@endsection
