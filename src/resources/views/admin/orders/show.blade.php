@extends('admin.layouts.app')

@section('title', $order->order_number.' | Orders | Anugerah3D Admin')
@section('page_title', 'Order '.$order->order_number)

@section('content')
    @php
        $statusClass = match ($order->status) {
            'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'processing' => 'bg-blue-50 text-blue-700 ring-blue-200',
            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            default => 'bg-red-50 text-red-700 ring-red-200',
        };
        $paymentClass = match ($order->payment_status) {
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'refunded' => 'bg-violet-50 text-violet-700 ring-violet-200',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    @endphp

    <div class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#1a73e8] hover:underline">
                <span aria-hidden="true"?�</span> Back to orders
            </a>
            <div class="flex flex-wrap gap-2">
                <span class="{{ $statusClass }} inline-flex rounded-full px-3 py-1.5 text-xs font-semibold ring-1">{{ $order->statusLabel() }}</span>
                <span class="{{ $paymentClass }} inline-flex rounded-full px-3 py-1.5 text-xs font-semibold ring-1">{{ $order->paymentStatusLabel() }}</span>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-700">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <p class="font-semibold">Action blocked</p>
                @foreach ($errors->all() as $error)
                    <p class="mt-1">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section class="rounded-lg bg-[linear-gradient(135deg,#111827,#1e293b)] p-5 text-white shadow-sm">
            <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-200">Agent order</p>
                    <h2 class="mt-2 font-mono text-2xl font-semibold">{{ $order->order_number }}</h2>
                    <p class="mt-2 text-sm text-slate-300">Placed {{ $order->placed_at->format('d M Y, h:i A') }} by {{ $order->agent->agt_name }}</p>
                </div>
                <div class="lg:text-right">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-300">Order total</p>
                    <p class="mt-1 text-3xl font-semibold">RM {{ number_format((float) $order->total_amount, 2) }}</p>
                    <p class="mt-1 text-xs text-slate-300">{{ $order->total_units }} units � Delivery fee to be confirmed</p>
                    <p class="mt-1 text-xs text-slate-300">Cost: RM {{ number_format((float) $order->total_cost, 2) }} � Profit: RM {{ number_format((float) $order->profit_amount, 2) }}</p>
                    @if ((float) $order->bonus_total > 0)
                        <p class="mt-1 text-xs text-slate-300">
                            Bonus RM {{ number_format((float) $order->bonus_total, 2) }} (Tier1 RM {{ number_format((float) $order->tier1_bonus, 2) }} + Tier2 RM {{ number_format((float) $order->tier2_bonus, 2) }})
                            <span class="ml-1 inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700" title="Rate used: Tier1 {{ number_format((float) $order->tier1_bonus_rate, 2) }}% and Tier2 {{ number_format((float) $order->tier2_bonus_rate, 2) }}%">i</span>
                        </p>
                    @endif
                </div>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-5">
                <section class="rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="font-semibold text-slate-950">Products and stock readiness</h2>
                                <p class="mt-1 text-sm text-slate-500">Processing is blocked until every unreserved unit is available.</p>
                            </div>
                            @if ($stockShortages->isEmpty())
                                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Stock ready</span>
                            @else
                                <span class="rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 ring-1 ring-red-200">{{ $stockShortages->count() }} shortage</span>
                            @endif
                        </div>
                    </div>

                    @if ($stockShortages->isNotEmpty())
                        <div class="border-b border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                            <p class="font-semibold">Insufficient product balance</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($stockShortages as $shortage)
                                    <li>{{ $shortage['product_name'] }} requires {{ $shortage['required'] }} more units; only {{ $shortage['available'] }} available.</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Product</th>
                                    <th class="px-4 py-3 text-right font-semibold">Qty</th>
                                    <th class="px-4 py-3 text-right font-semibold">Agent price</th>
                                    <th class="px-4 py-3 text-right font-semibold">Line total</th>
                                    <th class="px-4 py-3 text-right font-semibold">Financial</th>
                                    <th class="px-4 py-3 text-right font-semibold">Reserved</th>
                                    <th class="px-4 py-3 text-right font-semibold">Balance</th>
                                    <th class="px-4 py-3 text-left font-semibold">Readiness</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($order->items as $item)
                                    @php
                                        $missing = $item->missingReservationQuantity();
                                        $available = max(0, (int) $item->product->prd_balance);
                                        $insufficient = $missing > $available;
                                        $lineCost = (float) ($item->product?->cost_rm ?? 0) * (int) $item->quantity;
                                        $lineTier1Bonus = $order->agent?->referrer
                                            ? round((float) $item->line_total * ((float) $order->tier1_bonus_rate / 100), 2)
                                            : 0.0;
                                        $lineTier2Bonus = $order->agent?->referrer?->referrer
                                            ? round((float) $item->line_total * ((float) $order->tier2_bonus_rate / 100), 2)
                                            : 0.0;
                                        $lineBonus = round($lineTier1Bonus + $lineTier2Bonus, 2);
                                        $lineProfit = (float) $item->line_total - $lineCost - $lineBonus;
                                        $picturePath = $item->product?->prd_picture;
                                        $pictureUrl = $picturePath
                                            ? (filter_var($picturePath, FILTER_VALIDATE_URL) ? $picturePath : asset(ltrim((string) $picturePath, '/')))
                                            : null;
                                    @endphp
                                    <tr class="align-top">
                                        <td class="px-4 py-4">
                                            <div class="flex items-start gap-3">
                                                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                                    @if ($pictureUrl)
                                                        <img src="{{ $pictureUrl }}" alt="{{ $item->product_name }}" loading="lazy" class="h-full w-full object-cover">
                                                    @else
                                                        <div class="grid h-full w-full place-items-center text-slate-300">
                                                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="m4 16 4-4 4 4 3-3 5 5"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[15px] font-extrabold leading-tight text-slate-900">{{ $item->product_name }}</p>
                                                    <p class="mt-1 font-mono text-xs text-slate-500">{{ $item->product_code }}</p>
                                                </div>
                                            </div>
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @if ($item->is_preorder)
                                                    <span class="rounded-full bg-orange-50 px-2 py-0.5 text-[0.68rem] font-semibold text-orange-700">Pre-order</span>
                                                @endif
                                                @if ($item->product->materialType?->name || $item->product->material)
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.68rem] text-slate-600">{{ $item->product->materialType?->name ?: $item->product->material }}</span>
                                                @endif
                                                @if ($item->product->color)
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.68rem] text-slate-600">{{ $item->product->color }}</span>
                                                @endif
                                            </div>
                                            <p class="mt-2 text-xs text-slate-500">Selling RM {{ number_format((float) $item->unit_selling_price, 2) }} � {{ number_format((float) $item->discount_percentage, 1) }}% discount</p>
                                        </td>
                                        <td class="px-4 py-4 text-right font-semibold text-slate-900">{{ $item->quantity }}</td>
                                        <td class="px-4 py-4 text-right text-slate-700">RM {{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td class="px-4 py-4 text-right font-semibold text-slate-900">RM {{ number_format((float) $item->line_total, 2) }}</td>
                                        <td class="px-4 py-4 text-right">
                                            <p class="text-xs text-slate-500">Cost: RM {{ number_format($lineCost, 2) }}</p>
                                            <p class="mt-1 text-xs text-slate-500">Bonus: RM {{ number_format($lineBonus, 2) }}</p>
                                            <p class="mt-1 text-sm font-extrabold text-slate-900">RM {{ number_format($lineProfit, 2) }}</p>
                                        </td>
                                        <td class="px-4 py-4 text-right text-slate-700">{{ $item->reserved_quantity }}</td>
                                        <td class="px-4 py-4 text-right text-slate-700">{{ $available }}</td>
                                        <td class="px-4 py-4">
                                            @if ($missing === 0)
                                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Reserved</span>
                                            @elseif ($insufficient)
                                                <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Short {{ $missing - $available }}</span>
                                            @else
                                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ $missing }} available</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-slate-200 bg-slate-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-medium text-slate-600">Products subtotal</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">RM {{ number_format((float) $order->subtotal, 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <p class="text-xs text-slate-500">Cost: RM {{ number_format((float) $order->total_cost, 2) }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Bonus: RM {{ number_format((float) $order->bonus_total, 2) }}</p>
                                        <p class="mt-1 text-sm font-extrabold text-slate-900">RM {{ number_format((float) $order->profit_amount, 2) }}</p>
                                    </td>
                                    <td colspan="3"></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-medium text-slate-600">Delivery fee</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $order->delivery_fee === null ? 'To be confirmed' : 'RM '.number_format((float) $order->delivery_fee, 2) }}</td>
                                    <td colspan="4"></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right font-semibold text-slate-900">Order total</td>
                                    <td class="px-4 py-3 text-right text-lg font-semibold text-[#1a73e8]">RM {{ number_format((float) $order->total_amount, 2) }}</td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <section class="grid gap-5 lg:grid-cols-2">
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                        <h2 class="font-semibold text-slate-950">Fulfilment details</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Method</dt><dd class="mt-1 font-medium text-slate-900">{{ $order->fulfilmentLabel() }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Recipient</dt><dd class="mt-1 font-medium text-slate-900">{{ $order->recipient_name }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Phone</dt><dd class="mt-1 font-medium text-slate-900">{{ $order->phone_number }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">{{ $order->fulfilment_method === 'delivery' ? 'Address' : 'Pickup location' }}</dt><dd class="mt-1 leading-6 text-slate-700">{{ $order->delivery_address ?: 'Anugerah3D pickup counter' }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Order notes</dt><dd class="mt-1 leading-6 text-slate-700">{{ $order->notes ?: 'No notes provided.' }}</dd></div>
                        </dl>
                    </div>

                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                        <h2 class="font-semibold text-slate-950">Agent details</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Name</dt><dd class="mt-1 font-medium text-slate-900">{{ $order->agent->agt_name }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Login ID</dt><dd class="mt-1 font-mono text-slate-700">{{ $order->agent->login_id }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Email</dt><dd class="mt-1 break-all text-slate-700">{{ $order->agent->email }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Phone</dt><dd class="mt-1 text-slate-700">{{ $order->agent->phone_number ?: '-' }}</dd></div>
                        </dl>
                        <a href="{{ route('admin.agents.show', $order->agent) }}" class="mt-5 inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">View agent profile</a>
                    </div>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <h2 class="font-semibold text-slate-950">Order actions</h2>
                    <p class="mt-1 text-sm text-slate-500">Actions are recorded in the activity log.</p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('admin.orders.print.full', $order) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Print full</a>
                        <a href="{{ route('admin.orders.print.order', $order) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Print order</a>
                    </div>

                    <div class="mt-5 space-y-3">
                        @if ($order->status === 'pending')
                            @if ($stockShortages->isEmpty())
                                <form method="POST" action="{{ route('admin.orders.process', $order) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white transition hover:bg-[#1558b0]">Proceed to processing</button>
                                </form>
                                <p class="text-xs leading-5 text-slate-500">All remaining stock will be reserved when processing starts.</p>
                            @else
                                <button type="button" disabled class="inline-flex min-h-11 w-full cursor-not-allowed items-center justify-center rounded-lg bg-slate-200 px-4 text-sm font-semibold text-slate-500">Cannot process: insufficient stock</button>
                                <p class="text-xs leading-5 text-red-600">Update product balance first. The server will recheck every item before proceeding.</p>
                            @endif
                        @elseif ($order->status === 'processing')
                            <form method="POST" action="{{ route('admin.orders.complete', $order) }}">
                                @csrf
                                @method('PATCH')
                                <button class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700">Mark order completed</button>
                            </form>
                        @elseif ($order->status === 'completed')
                            <div class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-700">This order is complete and sales have been added to the agent's total.</div>
                        @else
                            <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700">This order was cancelled. Reserved stock has been restored.</div>
                        @endif

                        @if (in_array($order->status, ['pending', 'processing'], true))
                            <details class="rounded-lg border border-red-200 bg-red-50 p-3">
                                <summary class="cursor-pointer text-sm font-semibold text-red-700">Cancel order</summary>
                                <p class="mt-2 text-xs leading-5 text-red-600">Cancellation is final. Every reserved unit will be returned to product balance.</p>
                                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="mt-3">
                                    @csrf
                                    @method('PATCH')
                                    <button class="inline-flex min-h-10 w-full items-center justify-center rounded-lg bg-red-600 px-4 text-sm font-semibold text-white hover:bg-red-700">Confirm cancellation</button>
                                </form>
                            </details>
                        @endif
                    </div>
                </section>

                <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <h2 class="font-semibold text-slate-950">Payment</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $order->paymentMethodLabel() }}</p>

                    @if (count($order->paymentProofUrls()) > 0)
                        <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment proof</p>
                            <div class="mt-2 flex gap-2 overflow-x-auto pb-1">
                                @foreach ($order->paymentProofUrls() as $url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-white">
                                        <img src="{{ $url }}" alt="Payment proof" class="h-full w-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.orders.payment.update', $order) }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PATCH')
                        <label for="payment_status" class="block text-xs font-semibold uppercase text-slate-500">Payment status</label>
                        <select id="payment_status" name="payment_status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                            @foreach ($paymentStatuses as $paymentStatus)
                                <option value="{{ $paymentStatus }}" @selected($order->payment_status === $paymentStatus)>{{ Str::headline($paymentStatus) }}</option>
                            @endforeach
                        </select>
                        <button class="inline-flex min-h-10 w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Update payment</button>
                    </form>
                </section>

                <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <h2 class="font-semibold text-slate-950">Order timeline</h2>
                    <dl class="mt-4 space-y-4 border-l-2 border-slate-200 pl-4 text-sm">
                        <div><dt class="font-semibold text-slate-900">Order placed</dt><dd class="mt-1 text-xs text-slate-500">{{ $order->placed_at->format('d M Y, h:i A') }}</dd></div>
                        @if ($order->inventory_reserved_at)<div><dt class="font-semibold text-slate-900">Inventory reserved</dt><dd class="mt-1 text-xs text-slate-500">{{ $order->inventory_reserved_at->format('d M Y, h:i A') }}</dd></div>@endif
                        @if ($order->processed_at)<div><dt class="font-semibold text-slate-900">Processing started</dt><dd class="mt-1 text-xs text-slate-500">{{ $order->processed_at->format('d M Y, h:i A') }}</dd></div>@endif
                        @if ($order->completed_at)<div><dt class="font-semibold text-slate-900">Order completed</dt><dd class="mt-1 text-xs text-slate-500">{{ $order->completed_at->format('d M Y, h:i A') }}</dd></div>@endif
                        @if ($order->cancelled_at)<div><dt class="font-semibold text-red-700">Order cancelled</dt><dd class="mt-1 text-xs text-slate-500">{{ $order->cancelled_at->format('d M Y, h:i A') }}</dd></div>@endif
                    </dl>
                </section>

                <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <h2 class="font-semibold text-slate-950">Record information</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Database ID</dt><dd class="mt-1 font-mono text-slate-700">{{ $order->getKey() }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Admin email</dt><dd class="mt-1 text-slate-700">{{ $order->admin_notification_sent_at ? 'Sent '.$order->admin_notification_sent_at->format('d M Y, h:i A') : 'Not sent' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Last updated</dt><dd class="mt-1 text-slate-700">{{ $order->updated_at->format('d M Y, h:i A') }}</dd></div>
                    </dl>
                </section>
            </aside>
        </div>
    </div>
@endsection
