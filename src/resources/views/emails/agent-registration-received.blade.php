@php
    $logoPath = public_path('images/anugerah3d-logo.png');
    $logoUrl = isset($message) && file_exists($logoPath) ? $message->embed($logoPath) : asset('images/anugerah3d-logo.png');
    $referrerPictureUrl = null;
    if ($referrer->profile_picture) {
        $picturePath = public_path(ltrim($referrer->profile_picture, '/'));
        $referrerPictureUrl = filter_var($referrer->profile_picture, FILTER_VALIDATE_URL)
            ? $referrer->profile_picture
            : (isset($message) && file_exists($picturePath) ? $message->embed($picturePath) : asset($referrer->profile_picture));
    }
@endphp
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Agent application received</title></head>
<body style="margin:0;background:#eef3f6;font-family:Arial,Helvetica,sans-serif;color:#17324d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f6;padding:24px 10px;"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 12px 32px rgba(23,50,77,.12);">
<tr><td style="background:linear-gradient(135deg,#17324d,#285875);padding:30px;text-align:center;color:#fff;">
<img src="{{ $logoUrl }}" width="86" height="86" alt="Anugerah3D" style="display:block;margin:0 auto 16px;border-radius:20px;border:3px solid rgba(255,255,255,.8);object-fit:cover;">
<div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#fdba74;font-weight:800;">Application received</div>
<h1 style="margin:10px 0 8px;font-size:27px;line-height:1.2;">Welcome, {{ $agent->agt_name }}!</h1>
<p style="margin:0;color:#dbeafe;font-size:14px;line-height:1.6;">Your agent application is now pending administrator approval.</p>
</td></tr>
<tr><td style="padding:28px;">
<div style="border:1px solid #fde68a;background:#fffbeb;border-radius:14px;padding:14px 16px;color:#92400e;font-size:13px;line-height:1.6;"><strong>Status: Pending approval.</strong> Keep the login information below safe. You can sign in after your account is approved.</div>

<div style="margin-top:20px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:19px;">
<div style="font-size:11px;text-transform:uppercase;letter-spacing:1.4px;color:#e7682b;font-weight:800;margin-bottom:12px;">Your login information</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;">
<tr><td style="padding:5px 0;color:#64748b;width:120px;">Login ID</td><td style="padding:5px 0;font-weight:800;word-break:break-all;">{{ $agent->login_id }}</td></tr>
<tr><td style="padding:5px 0;color:#64748b;">Password</td><td style="padding:5px 0;font-weight:800;">{{ $plainPassword }}</td></tr>
<tr><td style="padding:5px 0;color:#64748b;">Login address</td><td style="padding:5px 0;"><a href="{{ $loginUrl }}" style="color:#2563eb;text-decoration:none;word-break:break-all;">{{ $loginUrl }}</a></td></tr>
</table>
</div>

<div style="margin-top:20px;">
<div style="font-size:11px;text-transform:uppercase;letter-spacing:1.4px;color:#64748b;font-weight:800;margin-bottom:10px;">Application information</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;line-height:1.6;border-collapse:collapse;">
<tr><td style="padding:7px 0;border-bottom:1px solid #e2e8f0;color:#64748b;width:120px;">Name</td><td style="padding:7px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ $agent->agt_name }}</td></tr>
<tr><td style="padding:7px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">Email</td><td style="padding:7px 0;border-bottom:1px solid #e2e8f0;">{{ $agent->email }}</td></tr>
<tr><td style="padding:7px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">WhatsApp</td><td style="padding:7px 0;border-bottom:1px solid #e2e8f0;">{{ $agent->phone_number }}</td></tr>
<tr><td style="padding:7px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">Address</td><td style="padding:7px 0;border-bottom:1px solid #e2e8f0;">{{ $agent->address }}</td></tr>
<tr><td style="padding:7px 0;color:#64748b;">City / State</td><td style="padding:7px 0;">{{ $agent->city }}, {{ $agent->state }}</td></tr>
</table>
</div>

<div style="margin-top:22px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:16px;padding:17px;">
<div style="font-size:11px;text-transform:uppercase;letter-spacing:1.2px;color:#2563eb;font-weight:800;margin-bottom:11px;">Your referrer</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
<td width="62">@if ($referrerPictureUrl)<img src="{{ $referrerPictureUrl }}" width="50" height="50" alt="{{ $referrer->agt_name }}" style="display:block;border-radius:50%;object-fit:cover;border:2px solid #fff;">@else<div style="width:50px;height:50px;line-height:50px;text-align:center;border-radius:50%;background:#17324d;color:#fff;font-weight:800;">{{ $referrer->initials() }}</div>@endif</td>
<td><div style="font-size:16px;font-weight:800;">{{ $referrer->agt_name }}</div><div style="margin-top:4px;font-size:13px;color:#475569;">{{ $referrer->phone_number ?: 'Anugerah3D agent' }}</div></td>
@if ($referrer->whatsappUrl())<td width="95" align="right"><a href="{{ $referrer->whatsappUrl('Hi '.$referrer->agt_name.', I have completed my Anugerah3D agent registration.') }}" style="display:inline-block;background:#16a34a;color:#fff;text-decoration:none;font-size:12px;font-weight:700;padding:10px;border-radius:10px;">WhatsApp</a></td>@endif
</tr></table>
</div>
<p style="margin:22px 0 0;text-align:center;color:#64748b;font-size:13px;line-height:1.6;">Thank you for choosing to grow with Anugerah3D. We are excited to have you with us!</p>
</td></tr>
<tr><td style="background:#17324d;padding:17px;text-align:center;color:#cbd5e1;font-size:12px;">Anugerah3D · Personalised ideas, made real.</td></tr>
</table></td></tr></table>
</body></html>
