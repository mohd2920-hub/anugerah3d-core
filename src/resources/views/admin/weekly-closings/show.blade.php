@extends('admin.layouts.app')

@section('title', 'Weekly Closing Detail | Anugerah3D Admin')
@section('page_title', 'Weekly Closing Detail')

@section('content')
<div class="space-y-5">
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-700">{{ session('success') }}</div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Week key</p>
                <h2 class="mt-1 text-xl font-bold text-slate-900">{{ $weeklyClosing->week_key }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $weeklyClosing->period_start->format('d M Y H:i') }} - {{ $weeklyClosing->period_end->format('d M Y H:i') }} (MYT)</p>
            </div>
            <a href="{{ route('admin.weekly-closings.index') }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to list</a>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total agents</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format($summary['total_rows']) }}</p></div>
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Pending</p><p class="mt-2 text-2xl font-semibold text-amber-900">{{ number_format($summary['pending_rows']) }}</p><p class="text-xs text-amber-800">RM {{ number_format((float) $summary['pending_bonus'], 2) }}</p></div>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Paid</p><p class="mt-2 text-2xl font-semibold text-emerald-900">{{ number_format($summary['paid_rows']) }}</p><p class="text-xs text-emerald-800">RM {{ number_format((float) $summary['paid_bonus'], 2) }}</p></div>
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Orders</p><p class="mt-2 text-2xl font-semibold text-blue-900">{{ number_format($weeklyClosing->total_orders) }}</p><p class="text-xs text-blue-800">RM {{ number_format((float) $weeklyClosing->total_order_amount, 2) }}</p></div>
        <div class="rounded-lg border border-violet-200 bg-violet-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-violet-700">POS</p><p class="mt-2 text-2xl font-semibold text-violet-900">{{ number_format($weeklyClosing->total_pos_sales) }}</p><p class="text-xs text-violet-800">RM {{ number_format((float) $weeklyClosing->total_pos_amount, 2) }}</p></div>
    </section>

    <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <form method="GET" action="{{ route('admin.weekly-closings.show', $weeklyClosing) }}" class="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_180px_auto]">
            <input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search agent or email" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
            <select name="payout_status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                <option value="">All status</option>
                <option value="pending" @selected($filters['payout_status'] === 'pending')>Pending</option>
                <option value="paid" @selected($filters['payout_status'] === 'paid')>Paid</option>
                <option value="no_payout" @selected($filters['payout_status'] === 'no_payout')>No payout</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white hover:bg-[#1558b0]">Filter</button>
                @if (collect($filters)->filter()->isNotEmpty())
                    <a href="{{ route('admin.weekly-closings.show', $weeklyClosing) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <section class="hidden overflow-visible rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70 md:block">
        <div class="overflow-x-auto">
            <table class="admin-data-table w-full min-w-[1050px] text-xs">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Agent</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Bank account</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">Personal</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">Tier 1 / Tier 2</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">POS</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">Bonus</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($rows as $row)
                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="px-3 py-3">
                                <p class="font-semibold text-slate-900">{{ $row->agent_name }}</p>
                                <p class="mt-1 text-slate-500">{{ $row->agent_email ?: '-' }}</p>
                                @if ($row->referrer_name)
                                    <p class="mt-1 text-[11px] text-slate-500">Referrer: {{ $row->referrer_name }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <p class="font-semibold text-slate-900">{{ $row->agent?->bank_name ?: '-' }}</p>
                                <p class="mt-1 text-[11px] text-slate-600">{{ $row->agent?->bank_account_name ?: '-' }}</p>
                                <p class="mt-1 text-[11px] font-mono text-slate-700">{{ $row->agent?->bank_account_number ?: '-' }}</p>
                            </td>
                            <td class="px-3 py-3 text-right"><p class="font-semibold text-slate-900">{{ number_format($row->personal_orders_count) }}</p><p class="text-[11px] text-slate-500">RM {{ number_format((float) $row->personal_order_amount, 2) }}</p></td>
                            <td class="px-3 py-3">
                                <div class="ml-auto grid w-fit grid-cols-2 gap-4 text-right">
                                    <div>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Tier 1</p>
                                        <p class="font-semibold text-slate-900">{{ number_format($row->tier1_orders_count) }}</p>
                                        <p class="text-[11px] text-slate-500">RM {{ number_format((float) $row->tier1_bonus, 2) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Tier 2</p>
                                        <p class="font-semibold text-slate-900">{{ number_format($row->tier2_orders_count) }}</p>
                                        <p class="text-[11px] text-slate-500">RM {{ number_format((float) $row->tier2_bonus, 2) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right"><p class="font-semibold text-slate-900">{{ number_format($row->pos_sales_count) }}</p><p class="text-[11px] text-slate-500">RM {{ number_format((float) $row->pos_sales_amount, 2) }}</p></td>
                            <td class="px-3 py-3 text-right font-semibold text-slate-900">RM {{ number_format((float) $row->total_bonus, 2) }}</td>
                            <td class="px-3 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[0.68rem] font-semibold',
                                    'bg-amber-50 text-amber-700 ring-1 ring-amber-200' => $row->payout_status === 'pending',
                                    'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' => $row->payout_status === 'paid',
                                    'bg-slate-100 text-slate-600' => $row->payout_status === 'no_payout',
                                ])>{{ Str::headline($row->payout_status) }}</span>
                                @if ($row->paid_at)
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $row->paid_at->format('d M, h:i A') }}</p>
                                @endif
                                @if ($row->notified_agent_at)
                                    <p class="mt-1 text-[11px] text-emerald-600">Agent notified {{ $row->notified_agent_at->format('d M, h:i A') }}</p>
                                @endif
                                <x-admin.weekly-closing-payment-button :row="$row" :weekly-closing="$weeklyClosing" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-10 text-center text-slate-600">No matching records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="grid gap-3 md:hidden">
        @forelse ($rows as $row)
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $row->agent_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $row->agent_email ?: '-' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[0.68rem] font-semibold text-slate-700">{{ Str::headline($row->payout_status) }}</span>
                        <x-admin.weekly-closing-payment-button :row="$row" :weekly-closing="$weeklyClosing" />
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-lg bg-slate-50 p-2"><p class="text-slate-500">POS</p><p class="font-semibold text-slate-900">RM {{ number_format((float) $row->pos_sales_amount, 2) }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-2"><p class="text-slate-500">Bonus</p><p class="font-semibold text-slate-900">RM {{ number_format((float) $row->total_bonus, 2) }}</p></div>
                </div>
                <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50/60 p-3 text-xs">
                    <p class="text-slate-500">Receipt date/time text</p>
                    <p class="mt-1 font-semibold text-slate-800">{{ $row->payment_receipt_datetime_text ?: '-' }}</p>
                    <p class="mt-2 text-slate-500">Reference</p>
                    <p class="mt-1 font-semibold text-slate-800">{{ $row->payment_reference ?: '-' }}</p>
                    <p class="mt-2 text-slate-500">Remark</p>
                    <p class="mt-1 font-semibold text-slate-800">{{ $row->payment_notes ?: '-' }}</p>

                    @if ($row->payment_attachment_path)
                        @php
                            $ext = strtolower(pathinfo((string) $row->payment_attachment_path, PATHINFO_EXTENSION));
                            $isImageProof = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                        @endphp
                        <div class="mt-3">
                            <p class="text-slate-500">Attachment</p>
                            @if ($isImageProof)
                                <a href="{{ asset($row->payment_attachment_path) }}" target="_blank" rel="noopener" class="mt-1 inline-flex rounded-md border border-slate-200 bg-white p-1 hover:border-blue-300">
                                    <img src="{{ asset($row->payment_attachment_path) }}" alt="Payment proof" class="h-20 w-20 rounded object-cover">
                                </a>
                            @endif
                            <a href="{{ asset($row->payment_attachment_path) }}" target="_blank" rel="noopener" class="mt-1 block font-semibold text-[#1a73e8] hover:underline">{{ $isImageProof ? 'Open full attachment' : 'View attachment' }}</a>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-600 shadow-sm">No matching records.</div>
        @endforelse
    </section>


    <div
        class="fixed inset-0 z-[80] hidden items-end justify-center bg-slate-950/60 p-0 backdrop-blur-sm sm:items-center sm:p-5"
        data-weekly-payment-modal
        data-open-summary-id="{{ old('modal_summary_id') }}"
        data-preserve-old-values="{{ $errors->any() ? 'true' : 'false' }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="weekly-payment-title"
    >
        <button type="button" class="absolute inset-0" data-close-weekly-payment aria-label="Close payment modal"></button>

        <div class="relative z-10 max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-2xl sm:max-w-2xl sm:rounded-2xl">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4 sm:px-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#1a73e8]">Weekly closing</p>
                    <h2 id="weekly-payment-title" class="mt-1 text-xl font-bold text-slate-900">Update payment</h2>
                    <p class="mt-1 text-sm text-slate-500">Complete the payout and email the agent automatically.</p>
                </div>
                <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100 text-xl text-slate-600 hover:bg-slate-200" data-close-weekly-payment aria-label="Close payment modal">&times;</button>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" data-weekly-payment-form>
                @csrf
                @method('PATCH')
                <input type="hidden" name="payout_status" value="paid">
                <input type="hidden" name="modal_summary_id" value="{{ old('modal_summary_id') }}" data-payment-summary-id>

                <div class="space-y-5 px-5 py-5 sm:px-6">
                    @if ($errors->any())
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <p class="font-semibold">Payment could not be completed.</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid gap-3 rounded-xl border border-blue-100 bg-blue-50/60 p-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Agent</p>
                            <p class="mt-1 font-bold text-slate-900" data-payment-agent-name>-</p>
                            <p class="mt-1 text-sm text-slate-600" data-payment-agent-email>-</p>
                        </div>
                        <div class="sm:text-right">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment amount</p>
                            <p class="mt-1 text-xl font-bold text-[#1a73e8]">RM <span data-payment-total-bonus>0.00</span></p>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Agent bank details</h3>
                        <dl class="mt-2 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-3">
                            <div><dt class="text-xs text-slate-500">Bank name</dt><dd class="mt-1 font-semibold text-slate-900" data-payment-bank-name>-</dd></div>
                            <div><dt class="text-xs text-slate-500">Account name</dt><dd class="mt-1 font-semibold text-slate-900" data-payment-bank-account-name>-</dd></div>
                            <div><dt class="text-xs text-slate-500">Account number</dt><dd class="mt-1 font-mono font-semibold text-slate-900" data-payment-bank-account-number>-</dd></div>
                        </dl>
                    </div>

                    <div>
                        <label for="payment_receipt_datetime_text" class="block text-sm font-semibold text-slate-700">Receipt date/time</label>
                        <input id="payment_receipt_datetime_text" name="payment_receipt_datetime_text" value="{{ old('payment_receipt_datetime_text') }}" required maxlength="200" placeholder="e.g. 27 Jul 2026, 3:30 PM" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                        @error('payment_receipt_datetime_text')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="payment_reference" class="block text-sm font-semibold text-slate-700">Reference</label>
                        <input id="payment_reference" name="payment_reference" value="{{ old('payment_reference') }}" maxlength="120" placeholder="Bank transfer reference" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                        @error('payment_reference')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="payment_notes" class="block text-sm font-semibold text-slate-700">Remark</label>
                        <textarea id="payment_notes" name="payment_notes" rows="3" maxlength="2000" placeholder="Optional payment remark" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">{{ old('payment_notes') }}</textarea>
                        @error('payment_notes')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="payment_attachment" class="block text-sm font-semibold text-slate-700">Payment proof</label>
                        <input id="payment_attachment" type="file" name="payment_attachment" accept=".jpg,.jpeg,.png,.webp,.pdf" class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-[#1a73e8]">
                        <p class="mt-1.5 text-xs text-slate-500">JPG, PNG, WebP, or PDF up to 5 MB.</p>
                        @error('payment_attachment')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        <a href="#" target="_blank" rel="noopener" class="mt-2 hidden text-sm font-semibold text-[#1a73e8] hover:underline" data-payment-existing-attachment>View existing payment proof</a>
                    </div>

                    <label for="notify_agent" class="flex cursor-pointer items-start gap-3 rounded-xl border border-blue-200 bg-blue-50/70 p-4">
                        <input type="hidden" name="notify_agent" value="0">
                        <input id="notify_agent" type="checkbox" name="notify_agent" value="1" @checked((string) old('notify_agent', '1') === '1') class="mt-0.5 h-5 w-5 rounded border-slate-300 text-[#1a73e8] focus:ring-[#1a73e8]">
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Notify agent by email</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-600">Send a payment confirmation to <strong data-payment-notify-email>-</strong>.</span>
                        </span>
                    </label>
                    @error('notify_agent')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="sticky bottom-0 grid grid-cols-2 gap-3 border-t border-slate-200 bg-white px-5 py-4 sm:px-6">
                    <button type="button" class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-close-weekly-payment>Cancel</button>
                    <button type="submit" class="min-h-11 rounded-xl bg-[#1a73e8] px-4 text-sm font-semibold text-white hover:bg-[#1558b0]">Confirm payment &amp; send email</button>
                </div>
            </form>
        </div>
    </div>

    <div class="flex justify-center">{{ $rows->links('pagination::tailwind') }}</div>
</div>
@endsection
