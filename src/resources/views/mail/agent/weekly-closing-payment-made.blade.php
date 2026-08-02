<x-mail::message>
<div style="overflow:hidden;border:1px solid #bbf7d0;border-radius:24px;background:#ffffff;box-shadow:0 18px 45px rgba(15,23,42,.08);margin-bottom:24px;">
    <div style="padding:24px;background:linear-gradient(135deg,#052e16 0%,#166534 55%,#22c55e 100%);">
        <img src="{{ asset('images/anugerah3d-logo.png') }}" alt="Anugerah3D" style="display:block;max-width:170px;height:auto;margin-bottom:22px;">
        <div style="display:inline-block;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.16);color:#dcfce7;font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;">Bayaran berjaya</div>
        <div style="margin-top:14px;color:#ffffff;font-size:28px;line-height:1.2;font-weight:900;">Weekly Closing anda telah dibayar</div>
        <div style="margin-top:10px;color:#dcfce7;font-size:15px;line-height:1.7;">Hi {{ $summary->agent_name }}, bayaran komisen anda telah diproses dengan jayanya.</div>
    </div>

    <div style="padding:22px 24px;text-align:center;background:#f0fdf4;">
        <div style="color:#64748b;font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;">Jumlah dibayar</div>
        <div style="margin-top:6px;color:#14532d;font-size:34px;line-height:1.1;font-weight:900;">RM {{ number_format((float) $summary->total_bonus, 2) }}</div>
        <div style="margin-top:8px;color:#166534;font-size:13px;">Minggu {{ $summary->closing->week_key }}</div>
    </div>
</div>

<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:20px;padding:20px 22px;margin-bottom:18px;">
    <div style="font-size:16px;font-weight:900;color:#0f172a;margin-bottom:14px;">Butiran transaksi</div>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td style="padding:8px 0;color:#64748b;font-size:13px;border-bottom:1px solid #f1f5f9;">Tempoh</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;font-size:13px;border-bottom:1px solid #f1f5f9;">{{ $summary->closing->period_start->format('d M Y') }} - {{ $summary->closing->period_end->copy()->subSecond()->format('d M Y') }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#64748b;font-size:13px;border-bottom:1px solid #f1f5f9;">Tarikh / masa bayaran</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;font-size:13px;border-bottom:1px solid #f1f5f9;">{{ $summary->payment_receipt_datetime_text }}</td>
        </tr>
        @if ($summary->payment_reference)
            <tr>
                <td style="padding:8px 0;color:#64748b;font-size:13px;border-bottom:1px solid #f1f5f9;">Rujukan</td>
                <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;font-size:13px;border-bottom:1px solid #f1f5f9;">{{ $summary->payment_reference }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:8px 0;color:#64748b;font-size:13px;">Bonus Tier 1</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;font-size:13px;">RM {{ number_format((float) $summary->tier1_bonus, 2) }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#64748b;font-size:13px;">Bonus Tier 2</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;font-size:13px;">RM {{ number_format((float) $summary->tier2_bonus, 2) }}</td>
        </tr>
    </table>
</div>

@if ($summary->agent_bank_name || $summary->agent_bank_account_name || $summary->agent_bank_account_number)
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:20px;padding:20px 22px;margin-bottom:18px;">
        <div style="font-size:16px;font-weight:900;color:#0f172a;margin-bottom:14px;">Dibayar ke akaun</div>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr><td style="padding:6px 0;color:#64748b;font-size:13px;">Bank</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#0f172a;font-size:13px;">{{ $summary->agent_bank_name ?: '-' }}</td></tr>
            <tr><td style="padding:6px 0;color:#64748b;font-size:13px;">Nama akaun</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#0f172a;font-size:13px;">{{ $summary->agent_bank_account_name ?: '-' }}</td></tr>
            <tr><td style="padding:6px 0;color:#64748b;font-size:13px;">Nombor akaun</td><td style="padding:6px 0;text-align:right;font-family:monospace;font-weight:800;color:#0f172a;font-size:14px;">{{ $summary->agent_bank_account_number ?: '-' }}</td></tr>
        </table>
    </div>
@endif

@if ($summary->payment_notes)
    <div style="padding:16px 18px;border-left:4px solid #60a5fa;border-radius:12px;background:#eff6ff;color:#1e3a8a;font-size:13px;line-height:1.7;margin-bottom:18px;">
        <strong>Catatan:</strong> {{ $summary->payment_notes }}
    </div>
@endif

@if ($summary->payment_attachment_path)
<x-mail::button :url="asset($summary->payment_attachment_path)" color="success">
Lihat bukti pembayaran
</x-mail::button>
@endif

<div style="margin-top:22px;padding:16px 18px;border-radius:14px;background:#f8fafc;color:#64748b;font-size:12px;line-height:1.7;">
    E-mel ini dihantar secara automatik sebagai pengesahan rasmi bayaran Weekly Closing anda. Sila simpan e-mel ini untuk rujukan.
</div>

Terima kasih,<br>
<strong>Pasukan Anugerah3D</strong>
</x-mail::message>
