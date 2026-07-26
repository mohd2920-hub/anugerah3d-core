<x-mail::message>
<div style="padding:24px 24px 18px;border-radius:24px;background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 46%,#e0f2fe 100%);border:1px solid #bfdbfe;box-shadow:0 18px 40px rgba(59,130,246,.12);margin-bottom:24px;">
    <div style="margin-bottom:14px;"><img src="{{ asset('images/anugerah3d-logo.png') }}" alt="Anugerah3D" style="display:block;max-width:180px;height:auto;"></div>
    <div style="font-size:12px;letter-spacing:.16em;font-weight:800;color:#1d4ed8;text-transform:uppercase;">Admin Weekly Closing</div>
    <div style="margin-top:10px;font-size:28px;line-height:1.15;font-weight:900;color:#0f172a;">Business Progress Summary</div>
    <div style="margin-top:10px;color:#1e40af;font-size:15px;line-height:1.6;">Weekly snapshot for {{ $closing->period_start->format('d M Y') }} - {{ $closing->period_end->subSecond()->format('d M Y') }} ({{ $closing->week_key }}).</div>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:18px;">
    <tr>
        <td style="padding:0 8px 8px 0;"><div style="background:#fff;border:1px solid #bfdbfe;border-radius:18px;padding:14px 16px;"><div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#1d4ed8;">Total orders</div><div style="margin-top:6px;font-size:20px;font-weight:900;color:#0f172a;">{{ number_format($closing->total_orders) }}</div></div></td>
        <td style="padding:0 0 8px 8px;"><div style="background:#fff;border:1px solid #fed7aa;border-radius:18px;padding:14px 16px;"><div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#c2410c;">Order amount</div><div style="margin-top:6px;font-size:20px;font-weight:900;color:#7c2d12;">RM {{ number_format((float) $closing->total_order_amount, 2) }}</div></div></td>
    </tr>
    <tr>
        <td style="padding:8px 8px 0 0;"><div style="background:#fff;border:1px solid #c4b5fd;border-radius:18px;padding:14px 16px;"><div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#6d28d9;">POS amount</div><div style="margin-top:6px;font-size:20px;font-weight:900;color:#4c1d95;">RM {{ number_format((float) $closing->total_pos_amount, 2) }}</div></div></td>
        <td style="padding:8px 0 0 8px;"><div style="background:#fff;border:1px solid #bbf7d0;border-radius:18px;padding:14px 16px;"><div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#15803d;">Payable bonus</div><div style="margin-top:6px;font-size:20px;font-weight:900;color:#14532d;">RM {{ number_format((float) $closing->total_payable_bonus, 2) }}</div></div></td>
    </tr>
</table>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(15,23,42,.06);margin-bottom:18px;">
    <div style="font-size:16px;font-weight:900;color:#111827;margin-bottom:12px;">Business KPIs</div>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Total agents included</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ number_format($closing->total_agents) }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Total order units</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ number_format($closing->total_order_units) }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">New tier 1 agents</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ number_format($closing->total_new_agents) }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Tier 1 orders</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ number_format($closing->total_tier1_orders) }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Tier 2 orders</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ number_format($closing->total_tier2_orders) }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">POS transactions</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ number_format($closing->total_pos_sales) }}</td></tr>
    </table>
</div>

<div style="background:#fff7ed;border:1px solid #fdba74;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(251,146,60,.08);margin-bottom:18px;">
    <div style="font-size:16px;font-weight:900;color:#7c2d12;margin-bottom:12px;">Top Pending Payouts</div>
    <x-mail::table>
| Agent | Tier 1 | Tier 2 | Total | Status |
| :-- | --: | --: | --: | :-- |
@forelse ($topPayouts as $row)
| {{ $row->agent_name }} | RM {{ number_format((float) $row->tier1_bonus, 2) }} | RM {{ number_format((float) $row->tier2_bonus, 2) }} | RM {{ number_format((float) $row->total_bonus, 2) }} | {{ Str::headline($row->payout_status) }} |
@empty
| - | RM 0.00 | RM 0.00 | RM 0.00 | - |
@endforelse
    </x-mail::table>
</div>

Thanks,<br>
Anugerah3D
</x-mail::message>
