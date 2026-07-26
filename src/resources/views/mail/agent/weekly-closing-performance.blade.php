<x-mail::message>
<div style="padding:24px 24px 18px;border-radius:24px;background:linear-gradient(135deg,#ecfeff 0%,#cffafe 45%,#e0f2fe 100%);border:1px solid #a5f3fc;box-shadow:0 18px 40px rgba(14,116,144,.13);margin-bottom:24px;">
    @if (! empty($data['brand']['logo_url']))
    <div style="margin-bottom:14px;">
        <img src="{{ $data['brand']['logo_url'] }}" alt="Anugerah3D" style="display:block;max-width:180px;height:auto;">
    </div>
    @endif
    <div style="font-size:12px;letter-spacing:.16em;font-weight:800;color:#0e7490;text-transform:uppercase;">Weekly Closing</div>
    <div style="margin-top:10px;font-size:28px;line-height:1.15;font-weight:900;color:#0f172a;">Performance Summary</div>
    <div style="margin-top:10px;color:#155e75;font-size:15px;line-height:1.6;">
        Hi {{ $data['agent']['name'] }}, this is your weekly closing snapshot for <strong>{{ $data['period_label'] }}</strong>.
    </div>
</div>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(15,23,42,.06);margin-bottom:18px;">
    <div style="font-size:16px;font-weight:900;color:#111827;margin-bottom:12px;">Agent Profile</div>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td style="width:80px;vertical-align:top;padding-right:12px;">
                @if (! empty($data['agent']['thumb_url']))
                <img src="{{ $data['agent']['thumb_url'] }}" alt="Agent profile" style="display:block;width:72px;height:72px;border-radius:9999px;object-fit:cover;border:2px solid #bae6fd;">
                @else
                <div style="width:72px;height:72px;border-radius:9999px;background:#e2e8f0;border:2px solid #cbd5e1;"></div>
                @endif
            </td>
            <td style="vertical-align:top;">
                <div style="font-size:16px;font-weight:800;color:#0f172a;">{{ $data['agent']['name'] }}</div>
                <div style="margin-top:4px;font-size:13px;color:#475569;">{{ $data['agent']['email'] ?: '-' }}</div>
                <div style="margin-top:2px;font-size:13px;color:#475569;">Login ID: {{ $data['agent']['login_id'] ?: '-' }}</div>
                <div style="margin-top:2px;font-size:13px;color:#475569;">Week: {{ $data['week_key'] }}</div>
            </td>
        </tr>
    </table>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:18px;">
    <tr>
        <td style="padding:0 8px 8px 0;"><div style="background:#fff;border:1px solid #a7f3d0;border-radius:18px;padding:14px 16px;box-shadow:0 8px 22px rgba(16,185,129,.08);"><div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#047857;">Tier 1 bonus</div><div style="margin-top:6px;font-size:22px;font-weight:900;color:#065f46;">RM {{ number_format((float) $data['bonus']['tier1_bonus'], 2) }}</div></div></td>
        <td style="padding:0 0 8px 8px;"><div style="background:#fff;border:1px solid #bae6fd;border-radius:18px;padding:14px 16px;box-shadow:0 8px 22px rgba(14,165,233,.08);"><div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#0369a1;">Tier 2 bonus</div><div style="margin-top:6px;font-size:22px;font-weight:900;color:#0c4a6e;">RM {{ number_format((float) $data['bonus']['tier2_bonus'], 2) }}</div></div></td>
    </tr>
    <tr>
        <td style="padding:8px 8px 0 0;"><div style="background:#fff;border:1px solid #ddd6fe;border-radius:18px;padding:14px 16px;box-shadow:0 8px 22px rgba(124,58,237,.08);"><div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#6d28d9;">Total payable</div><div style="margin-top:6px;font-size:22px;font-weight:900;color:#4c1d95;">RM {{ number_format((float) $data['bonus']['total_bonus'], 2) }}</div></div></td>
        <td style="padding:8px 0 0 8px;"><div style="background:#fff;border:1px solid #fecaca;border-radius:18px;padding:14px 16px;box-shadow:0 8px 22px rgba(239,68,68,.08);"><div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#b91c1c;">Payout status</div><div style="margin-top:6px;font-size:22px;font-weight:900;color:#7f1d1d;">{{ $data['bonus']['payout_status'] }}</div></div></td>
    </tr>
</table>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(15,23,42,.06);margin-bottom:18px;">
    <div style="font-size:16px;font-weight:900;color:#111827;margin-bottom:12px;">Personal Performance</div>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Personal orders</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $data['personal']['orders_count'] }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Personal order amount</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">RM {{ number_format((float) $data['personal']['orders_amount'], 2) }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">New direct agents</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $data['team']['new_agents_registered'] }}</td></tr>
    </table>
</div>

<div style="background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);border:1px solid #fde68a;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(245,158,11,.10);margin-bottom:18px;">
    <div style="font-size:16px;font-weight:900;color:#78350f;margin-bottom:12px;">Team Performance</div>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Total Tier 1 agents</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $data['team']['tier1_agents_total'] }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Total Tier 2 agents</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $data['team']['tier2_agents_total'] }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Tier 1 orders</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $data['team']['tier1_orders_count'] }} (RM {{ number_format((float) $data['team']['tier1_orders_amount'], 2) }})</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Tier 2 orders</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $data['team']['tier2_orders_count'] }} (RM {{ number_format((float) $data['team']['tier2_orders_amount'], 2) }})</td></tr>
    </table>
</div>

<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:20px;padding:18px 20px;box-shadow:0 10px 24px rgba(15,23,42,.05);margin-bottom:18px;">
    <div style="font-size:16px;font-weight:900;color:#111827;margin-bottom:12px;">POS Summary</div>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">POS transactions</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $data['pos']['sales_count'] }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">POS amount</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">RM {{ number_format((float) $data['pos']['sales_amount'], 2) }}</td></tr>
    </table>
</div>

<div style="background:#ffffff;border:1px solid #d1fae5;border-radius:20px;padding:18px 20px;box-shadow:0 10px 24px rgba(16,185,129,.08);margin-bottom:18px;">
    <div style="font-size:16px;font-weight:900;color:#065f46;margin-bottom:12px;">Referrer Contact</div>
    @if ($data['referrer']['exists'])
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td style="width:80px;vertical-align:top;padding:4px 12px 10px 0;">
                @if (! empty($data['referrer']['thumb_url']))
                <img src="{{ $data['referrer']['thumb_url'] }}" alt="Referrer profile" style="display:block;width:72px;height:72px;border-radius:9999px;object-fit:cover;border:2px solid #bbf7d0;">
                @else
                <div style="width:72px;height:72px;border-radius:9999px;background:#e2e8f0;border:2px solid #cbd5e1;"></div>
                @endif
            </td>
            <td></td>
        </tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Name</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $data['referrer']['name'] }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Email</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $data['referrer']['email'] ?: '-' }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Phone</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $data['referrer']['phone'] ?: '-' }}</td></tr>
        <tr><td style="padding:6px 0;color:#6b7280;font-size:13px;">Login ID</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $data['referrer']['login_id'] ?: '-' }}</td></tr>
    </table>
    @if ($data['referrer']['whatsapp_url'])
    <x-mail::button :url="$data['referrer']['whatsapp_url']">Contact Referrer</x-mail::button>
    @endif
    @else
    <div style="font-size:13px;color:#6b7280;">No referrer record is linked for this agent.</div>
    @endif
</div>

Thanks,<br>
Anugerah3D
</x-mail::message>
