@php
    $logoPath = public_path('images/anugerah3d-logo.png');
    $logoUrl = isset($message) && file_exists($logoPath) ? $message->embed($logoPath) : asset('images/anugerah3d-logo.png');
    $agentPictureUrl = null;
    $referrerPictureUrl = null;

    if ($agent->profile_picture) {
        $agentPicturePath = public_path(ltrim($agent->profile_picture, '/'));
        $agentPictureUrl = filter_var($agent->profile_picture, FILTER_VALIDATE_URL)
            ? $agent->profile_picture
            : (isset($message) && file_exists($agentPicturePath) ? $message->embed($agentPicturePath) : asset($agent->profile_picture));
    }

    if ($referrer?->profile_picture) {
        $referrerPicturePath = public_path(ltrim($referrer->profile_picture, '/'));
        $referrerPictureUrl = filter_var($referrer->profile_picture, FILTER_VALIDATE_URL)
            ? $referrer->profile_picture
            : (isset($message) && file_exists($referrerPicturePath) ? $message->embed($referrerPicturePath) : asset($referrer->profile_picture));
    }
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Anugerah3D agent account is ready</title>
</head>
<body style="margin:0;background:#eef3f6;font-family:Arial,Helvetica,sans-serif;color:#17324d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f6;padding:24px 12px;">
<tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 12px 32px rgba(23,50,77,.12);">
        <tr>
            <td style="background:linear-gradient(135deg,#17324d,#285875);padding:30px 30px 34px;text-align:center;color:#ffffff;">
                <img src="{{ $logoUrl }}" width="92" height="92" alt="Anugerah3D" style="display:block;margin:0 auto 18px;border-radius:22px;border:3px solid rgba(255,255,255,.8);object-fit:cover;">
                <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#fdba74;font-weight:700;">Welcome aboard</div>
                <h1 style="margin:10px 0 8px;font-size:28px;line-height:1.2;color:#ffffff;">Your agent account is ready!</h1>
                <p style="margin:0;color:#dbeafe;font-size:15px;line-height:1.6;">Congratulations {{ $agent->agt_name }}. Your Anugerah3D agent registration has been approved.</p>
            </td>
        </tr>
        <tr>
            <td style="padding:28px 30px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px;">
                    <tr>
                        @if ($agentPictureUrl)
                            <td width="64" valign="top"><img src="{{ $agentPictureUrl }}" width="54" height="54" alt="{{ $agent->agt_name }}" style="display:block;border-radius:50%;object-fit:cover;border:2px solid #fed7aa;"></td>
                        @endif
                        <td valign="middle">
                            <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:1px;font-weight:700;">New agent</div>
                            <div style="margin-top:4px;font-size:20px;font-weight:800;color:#17324d;">{{ $agent->agt_name }}</div>
                            <div style="margin-top:3px;font-size:13px;color:#64748b;">{{ $agent->email }} · {{ $agent->phone_number }}</div>
                        </td>
                    </tr>
                </table>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:20px;margin-bottom:22px;">
                    <div style="font-size:12px;text-transform:uppercase;letter-spacing:1.4px;color:#e7682b;font-weight:800;margin-bottom:14px;">Your login details</div>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;">
                        <tr><td style="padding:5px 0;color:#64748b;width:115px;">Login ID</td><td style="padding:5px 0;font-weight:800;color:#17324d;">{{ $agent->login_id }}</td></tr>
                        <tr><td style="padding:5px 0;color:#64748b;">Password</td><td style="padding:5px 0;font-weight:800;color:#17324d;">{{ $plainPassword }}</td></tr>
                        <tr><td style="padding:5px 0;color:#64748b;">Login address</td><td style="padding:5px 0;"><a href="{{ $loginUrl }}" style="color:#2563eb;text-decoration:none;word-break:break-all;">{{ $loginUrl }}</a></td></tr>
                    </table>
                </div>

                <div style="text-align:center;margin-bottom:26px;">
                    <a href="{{ $loginUrl }}" style="display:inline-block;background:#e7682b;color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;padding:14px 28px;border-radius:12px;">Login to Agent Workspace</a>
                </div>

                @if ($referrer)
                    <div style="border:1px solid #bfdbfe;background:#eff6ff;border-radius:16px;padding:18px;">
                        <div style="font-size:12px;text-transform:uppercase;letter-spacing:1.2px;color:#2563eb;font-weight:800;margin-bottom:12px;">Your referrer</div>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="64" valign="middle">
                                    @if ($referrerPictureUrl)
                                        <img src="{{ $referrerPictureUrl }}" width="52" height="52" alt="{{ $referrer->agt_name }}" style="display:block;border-radius:50%;object-fit:cover;border:2px solid #ffffff;">
                                    @else
                                        <div style="width:52px;height:52px;line-height:52px;text-align:center;border-radius:50%;background:#17324d;color:#ffffff;font-weight:800;">{{ $referrer->initials() }}</div>
                                    @endif
                                </td>
                                <td valign="middle">
                                    <div style="font-size:17px;font-weight:800;color:#17324d;">{{ $referrer->agt_name }}</div>
                                    <div style="margin-top:4px;font-size:14px;color:#475569;">WhatsApp: {{ $referrer->phone_number ?: '-' }}</div>
                                </td>
                                @if ($referrer->whatsappUrl())
                                    <td width="105" align="right" valign="middle"><a href="{{ $referrer->whatsappUrl('Hi '.$referrer->agt_name.', I have received my Anugerah3D agent account details.') }}" style="display:inline-block;background:#16a34a;color:#ffffff;text-decoration:none;font-size:12px;font-weight:700;padding:10px 12px;border-radius:10px;">WhatsApp</a></td>
                                @endif
                            </tr>
                        </table>
                        <p style="margin:13px 0 0;font-size:13px;line-height:1.5;color:#64748b;">You may contact your referrer if you need guidance getting started.</p>
                    </div>
                @endif

                <p style="margin:22px 0 0;font-size:12px;line-height:1.6;color:#94a3b8;text-align:center;">For security, sign in and change your initial password from your profile after your first login.</p>
            </td>
        </tr>
        <tr><td style="background:#17324d;padding:18px 30px;text-align:center;color:#cbd5e1;font-size:12px;">Anugerah3D · Personalised ideas, made real.</td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
