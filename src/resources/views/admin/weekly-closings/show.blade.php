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
            <table class="admin-data-table w-full min-w-[1250px] text-xs">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Agent</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Bank account</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">Personal</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">Tier 1</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">Tier 2</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">Bonus</th>
                        <th class="px-3 py-3 text-right font-semibold text-slate-700">POS</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Status</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Payment update</th>
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
                                <p class="font-semibold text-slate-900">{{ $row->agent_bank_name ?: '-' }}</p>
                                <p class="mt-1 text-[11px] text-slate-600">{{ $row->agent_bank_account_name ?: '-' }}</p>
                                <p class="mt-1 text-[11px] font-mono text-slate-700">{{ $row->agent_bank_account_number ?: '-' }}</p>
                            </td>
                            <td class="px-3 py-3 text-right"><p class="font-semibold text-slate-900">{{ number_format($row->personal_orders_count) }}</p><p class="text-[11px] text-slate-500">RM {{ number_format((float) $row->personal_order_amount, 2) }}</p></td>
                            <td class="px-3 py-3 text-right"><p class="font-semibold text-slate-900">{{ number_format($row->tier1_orders_count) }}</p><p class="text-[11px] text-slate-500">RM {{ number_format((float) $row->tier1_bonus, 2) }}</p></td>
                            <td class="px-3 py-3 text-right"><p class="font-semibold text-slate-900">{{ number_format($row->tier2_orders_count) }}</p><p class="text-[11px] text-slate-500">RM {{ number_format((float) $row->tier2_bonus, 2) }}</p></td>
                            <td class="px-3 py-3 text-right font-semibold text-slate-900">RM {{ number_format((float) $row->total_bonus, 2) }}</td>
                            <td class="px-3 py-3 text-right"><p class="font-semibold text-slate-900">{{ number_format($row->pos_sales_count) }}</p><p class="text-[11px] text-slate-500">RM {{ number_format((float) $row->pos_sales_amount, 2) }}</p></td>
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
                            </td>
                            <td class="px-3 py-3">
                                @if ($row->payout_status === 'no_payout')
                                    <p class="text-xs text-slate-500">No payout for this week.</p>
                                @else
                                    <form method="POST" action="{{ route('admin.weekly-closings.payments.update', [$weeklyClosing, $row]) }}" enctype="multipart/form-data" class="grid gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="payout_status" class="h-8 rounded-md border border-slate-300 px-2 text-xs">
                                            <option value="pending" @selected($row->payout_status === 'pending')>Pending</option>
                                            <option value="paid" @selected($row->payout_status === 'paid')>Paid</option>
                                        </select>
                                        <input name="payment_receipt_datetime_text" value="{{ $row->payment_receipt_datetime_text }}" placeholder="Receipt date/time text" class="h-8 rounded-md border border-slate-300 px-2 text-xs">
                                        <input name="payment_reference" value="{{ $row->payment_reference }}" placeholder="Reference" class="h-8 rounded-md border border-slate-300 px-2 text-xs">
                                        <input name="payment_notes" value="{{ $row->payment_notes }}" placeholder="Remark" class="h-8 rounded-md border border-slate-300 px-2 text-xs">
                                        <input type="file" name="payment_attachment" accept=".jpg,.jpeg,.png,.webp,.pdf" class="h-8 rounded-md border border-slate-300 px-2 text-[11px]">
                                        @if ($row->payment_attachment_path)
                                            @php
                                                $ext = strtolower(pathinfo((string) $row->payment_attachment_path, PATHINFO_EXTENSION));
                                                $isImageProof = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                            @endphp
                                            @if ($isImageProof)
                                                <a href="{{ asset($row->payment_attachment_path) }}" target="_blank" rel="noopener" class="inline-flex w-fit rounded-md border border-slate-200 bg-white p-1 hover:border-blue-300">
                                                    <img src="{{ asset($row->payment_attachment_path) }}" alt="Payment proof" class="h-16 w-16 rounded object-cover">
                                                </a>
                                            @endif
                                            <a href="{{ asset($row->payment_attachment_path) }}" target="_blank" rel="noopener" class="text-[11px] font-semibold text-[#1a73e8] hover:underline">{{ $isImageProof ? 'Open full attachment' : 'View existing attachment' }}</a>
                                        @endif
                                        <label class="inline-flex items-center gap-2 text-[11px] text-slate-600">
                                            <input type="checkbox" name="notify_agent" value="1" class="rounded border-slate-300 text-[#1a73e8] focus:ring-[#1a73e8]"> Notify agent by email
                                        </label>
                                        <button class="inline-flex min-h-8 items-center justify-center rounded-md bg-[#1a73e8] px-2 text-xs font-semibold text-white">Save</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-6 py-10 text-center text-slate-600">No matching records.</td></tr>
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
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[0.68rem] font-semibold text-slate-700">{{ Str::headline($row->payout_status) }}</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-lg bg-slate-50 p-2"><p class="text-slate-500">Bonus</p><p class="font-semibold text-slate-900">RM {{ number_format((float) $row->total_bonus, 2) }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-2"><p class="text-slate-500">POS</p><p class="font-semibold text-slate-900">RM {{ number_format((float) $row->pos_sales_amount, 2) }}</p></div>
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

    <div class="flex justify-center">{{ $rows->links('pagination::tailwind') }}</div>
</div>
@endsection
