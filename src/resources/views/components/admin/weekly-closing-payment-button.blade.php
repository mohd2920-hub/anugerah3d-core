@props(['row', 'weeklyClosing'])

@if ($row->payout_status !== 'no_payout')
    <button
        type="button"
        class="mt-2 inline-flex min-h-8 items-center justify-center rounded-md bg-[#1a73e8] px-3 text-xs font-semibold text-white hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-blue-300"
        data-open-weekly-payment
        data-summary-id="{{ $row->id }}"
        data-action="{{ route('admin.weekly-closings.payments.update', [$weeklyClosing, $row]) }}"
        data-agent-name="{{ $row->agent_name }}"
        data-agent-email="{{ $row->agent?->email ?? $row->agent_email }}"
        data-bank-name="{{ $row->agent?->bank_name }}"
        data-bank-account-name="{{ $row->agent?->bank_account_name }}"
        data-bank-account-number="{{ $row->agent?->bank_account_number }}"
        data-total-bonus="{{ number_format((float) $row->total_bonus, 2, '.', '') }}"
        data-receipt-datetime="{{ $row->payment_receipt_datetime_text }}"
        data-reference="{{ $row->payment_reference }}"
        data-notes="{{ $row->payment_notes }}"
        data-attachment-url="{{ $row->payment_attachment_path ? asset($row->payment_attachment_path) : '' }}"
    >
        Update payment
    </button>
@endif
