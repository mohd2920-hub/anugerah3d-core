@php
    $logoPath = public_path("images/anugerah3d-logo.png");
    $logoUrl = isset($message) && file_exists($logoPath) ? $message->embed($logoPath) : asset("images/anugerah3d-logo.png");
    $mailMessage = $message ?? null;
    $templateImageUrls = collect($template->imagePaths())
        ->filter(static fn (string $path): bool => file_exists(public_path($path)))
        ->map(static fn (string $path): string => $mailMessage
            ? $mailMessage->embed(public_path($path))
            : asset($path))
        ->all();
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $template->subject }}</title>
</head>
<body style="margin:0;background:#eef3f6;font-family:Arial,Helvetica,sans-serif;color:#17324d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f6;padding:24px 12px;">
<tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 12px 32px rgba(23,50,77,.12);">
        <tr>
            <td style="background:linear-gradient(135deg,#17324d,#285875);padding:30px 30px 34px;text-align:center;color:#ffffff;">
                <img src="{{ $logoUrl }}" width="92" height="92" alt="Anugerah3D" style="display:block;margin:0 auto 18px;border-radius:22px;border:3px solid rgba(255,255,255,.8);object-fit:cover;">
                <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#fdba74;font-weight:700;">Anugerah3D</div>
                <h1 style="margin:10px 0 8px;font-size:28px;line-height:1.2;color:#ffffff;">{{ $template->subject }}</h1>
                <p style="margin:0;color:#dbeafe;font-size:15px;line-height:1.6;">Official email from the Anugerah3D admin platform.</p>
            </td>
        </tr>
        <tr>
            <td style="padding:28px 30px;">
                <div style="font-size:16px;font-weight:700;color:#17324d;">Hi {{ $recipient->agt_name }},</div>
                @if ($template->image_position === \App\Models\AgentEmailTemplate::ImagePositionTop)
                    <div style="margin-top:18px;">
                        <x-mail.agent-template-images :image-urls="$templateImageUrls" />
                    </div>
                @endif
                <div style="margin-top:18px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:20px;font-size:15px;line-height:1.8;color:#334155;">
                    {!! nl2br(e($template->body)) !!}
                </div>
                @if ($template->image_position === \App\Models\AgentEmailTemplate::ImagePositionBottom)
                    <div style="margin-top:18px;">
                        <x-mail.agent-template-images :image-urls="$templateImageUrls" />
                    </div>
                @endif
                <p style="margin:22px 0 0;font-size:13px;line-height:1.7;color:#64748b;">If you need help, please reply to the official Anugerah3D support or contact your admin team.</p>
            </td>
        </tr>
        <tr><td style="background:#17324d;padding:18px 30px;text-align:center;color:#cbd5e1;font-size:12px;">Anugerah3D · Personalised ideas, made real.</td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
