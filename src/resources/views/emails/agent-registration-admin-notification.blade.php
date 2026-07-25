@php
    $logoPath = public_path('images/anugerah3d-logo.png');
    $logoUrl = isset($message) && file_exists($logoPath) ? $message->embed($logoPath) : asset('images/anugerah3d-logo.png');
@endphp
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>New agent registration</title></head>
<body style="margin:0;background:#eef3f6;font-family:Arial,Helvetica,sans-serif;color:#17324d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f6;padding:24px 10px;"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#fff;border-radius:22px;overflow:hidden;box-shadow:0 12px 32px rgba(23,50,77,.12);">
<tr><td style="background:#17324d;padding:26px;text-align:center;color:#fff;"><img src="{{ $logoUrl }}" width="72" height="72" alt="Anugerah3D" style="display:block;margin:0 auto 14px;border-radius:18px;object-fit:cover;"><div style="font-size:11px;letter-spacing:1.8px;text-transform:uppercase;color:#fdba74;font-weight:800;">Admin notification</div><h1 style="margin:9px 0 5px;font-size:24px;">New agent pending approval</h1><p style="margin:0;color:#cbd5e1;font-size:13px;">A new referral registration requires review.</p></td></tr>
<tr><td style="padding:27px;">
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:16px;padding:18px;"><div style="font-size:20px;font-weight:800;">{{ $agent->agt_name }}</div><div style="margin-top:5px;color:#2563eb;font-size:13px;font-weight:700;">Status: Pending approval</div></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;font-size:13px;line-height:1.6;border-collapse:collapse;">
<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#64748b;width:125px;">Login / Email</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;font-weight:700;word-break:break-all;">{{ $agent->email }}</td></tr>
<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">WhatsApp</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;">{{ $agent->phone_number }}</td></tr>
<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">Address</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;">{{ $agent->address }}</td></tr>
<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">City / State</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;">{{ $agent->city }}, {{ $agent->state }}</td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Referrer</td><td style="padding:8px 0;font-weight:700;">{{ $referrer->agt_name }} · {{ $referrer->phone_number ?: '-' }} · {{ $referrer->login_id }}</td></tr>
</table>
<div style="margin-top:24px;text-align:center;"><a href="{{ $reviewUrl }}" style="display:inline-block;background:#e7682b;color:#fff;text-decoration:none;font-size:14px;font-weight:800;padding:13px 24px;border-radius:11px;">Review agent application</a></div>
<p style="margin:20px 0 0;text-align:center;color:#94a3b8;font-size:12px;line-height:1.6;">Assign the agent commission and approve the application from the Admin Console.</p>
</td></tr>
<tr><td style="background:#17324d;padding:16px;text-align:center;color:#cbd5e1;font-size:12px;">Anugerah3D Admin Console</td></tr>
</table></td></tr></table>
</body></html>
