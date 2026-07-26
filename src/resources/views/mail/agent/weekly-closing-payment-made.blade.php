<x-mail::message>
<div style="padding:24px 24px 18px;border-radius:24px;background:linear-gradient(135deg,#ecfdf5 0%,#dcfce7 46%,#d1fae5 100%);border:1px solid #86efac;box-shadow:0 18px 40px rgba(22,163,74,.12);margin-bottom:24px;">
    <div style="margin-bottom:14px;"><img src="{{ asset('images/anugerah3d-logo.png') }}" alt="Anugerah3D" style="display:block;max-width:180px;height:auto;"></div>
    <div style="font-size:12px;letter-spacing:.16em;font-weight:800;color:#15803d;text-transform:uppercase;">Payment Notification</div>
    <div style="margin-top:10px;font-size:28px;line-height:1.15;font-weight:900;color:#14532d;">Weekly Payout Paid</div>
    <div style="margin-top:10px;color:#166534;font-size:15px;line-height:1.6;">Hi {{ $summary->agent_name }}, your weekly closing payout has been marked as paid.</div>
</div>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(15,23,42,.06);margin-bottom:18px;">
    <div style="font-size:16px;font-weight:900;color:#111827;margin-bottom:12px;">Payout Details</div>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Week</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $summary->closing->week_key }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Period</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $summary->closing->period_start->format('d M Y') }} - {{ $summary->closing->period_end->subSecond()->format('d M Y') }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Tier 1 bonus</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">RM {{ number_format((float) $summary->tier1_bonus, 2) }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Tier 2 bonus</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">RM {{ number_format((float) $summary->tier2_bonus, 2) }}</td></tr>
        <tr><td style="padding:8px 0 0;color:#6b7280;font-size:13px;">Total paid</td><td style="padding:8px 0 0;text-align:right;font-weight:900;color:#14532d;font-size:18px;">RM {{ number_format((float) $summary->total_bonus, 2) }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Receipt date/time text</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $summary->payment_receipt_datetime_text ?: '-' }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Reference</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $summary->payment_reference ?: '-' }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Remark</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $summary->payment_notes ?: '-' }}</td></tr>
    </table>
</div>

@if ($summary->payment_attachment_path)
<x-mail::button :url="asset($summary->payment_attachment_path)">
View Payment Proof
</x-mail::button>
@endif

Thanks,<br>
Anugerah3D
</x-mail::message>
