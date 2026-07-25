@php
    $mailMessage = $message ?? null;
    $embedImage = function (?string $path) use ($mailMessage): ?string {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $relativePath = ltrim($path, '/');
        $absolutePath = public_path($relativePath);

        return $mailMessage && file_exists($absolutePath)
            ? $mailMessage->embed($absolutePath)
            : asset($relativePath);
    };

    $logoUrl = $embedImage('images/anugerah3d-logo.png');
    $seller = $sale->salesAgent;
    $sellerPictureUrl = $embedImage($seller?->profile_picture);
    $productPictures = [];

    foreach ($sale->items as $item) {
        $picturePath = $item->product?->images->first()?->image_path ?: $item->product?->prd_picture;
        $productPictures[$item->getKey()] = $embedImage($picturePath);
    }

    $grossTotal = $sale->items->sum(fn ($item) => (float) $item->unit_price * $item->quantity);
    $discountTotal = $sale->items->sum(fn ($item) => (float) $item->customer_discount_amount);
    $paymentLabel = \App\Models\PosSale::paymentMethods()[$sale->payment_method] ?? strtoupper($sale->payment_method);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Anugerah3D receipt</title>
</head>
<body style="margin:0;background:#eef3f6;font-family:Arial,Helvetica,sans-serif;color:#17324d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f6;padding:24px 10px;">
<tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 12px 32px rgba(23,50,77,.12);">
        <tr>
            <td style="background:linear-gradient(135deg,#17324d,#285875);padding:28px 26px;text-align:center;color:#ffffff;">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" width="82" height="82" alt="Anugerah3D" style="display:block;margin:0 auto 16px;border-radius:20px;border:3px solid rgba(255,255,255,.82);object-fit:cover;">
                @endif
                <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#fdba74;font-weight:700;">Purchase receipt</div>
                <h1 style="margin:9px 0 7px;font-size:28px;line-height:1.2;color:#ffffff;">Thank you for your purchase!</h1>
                <p style="margin:0;color:#dbeafe;font-size:15px;line-height:1.6;">Hi {{ $sale->customer_name ?: 'valued customer' }}, we truly appreciate your support.</p>
            </td>
        </tr>
        <tr>
            <td style="padding:26px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px;background:#fff7ed;border:1px solid #fed7aa;border-radius:16px;">
                    <tr>
                        <td style="padding:16px;">
                            <div style="font-size:11px;text-transform:uppercase;letter-spacing:1.2px;color:#e7682b;font-weight:800;">Receipt number</div>
                            <div style="margin-top:5px;font-size:17px;font-weight:800;color:#17324d;">{{ $sale->sale_number }}</div>
                        </td>
                        <td align="right" style="padding:16px;">
                            <div style="font-size:12px;color:#64748b;">{{ $sale->sold_at?->format('d M Y, h:i A') }}</div>
                            <div style="margin-top:5px;font-size:13px;font-weight:700;color:#17324d;">{{ $sale->businessSite?->site_name }}{{ $sale->businessSite?->city ? ' · '.$sale->businessSite->city : '' }}</div>
                        </td>
                    </tr>
                </table>

                <div style="font-size:12px;text-transform:uppercase;letter-spacing:1.2px;color:#64748b;font-weight:800;margin-bottom:10px;">Items purchased</div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    @foreach ($sale->items as $item)
                        <tr>
                            <td width="66" valign="middle" style="padding:11px 0;border-bottom:1px solid #e2e8f0;">
                                @if ($productPictures[$item->getKey()] ?? null)
                                    <img src="{{ $productPictures[$item->getKey()] }}" width="54" height="54" alt="{{ $item->product_name }}" style="display:block;border-radius:12px;object-fit:cover;border:1px solid #e2e8f0;">
                                @else
                                    <div style="width:54px;height:54px;line-height:54px;text-align:center;border-radius:12px;background:#f1f5f9;color:#94a3b8;font-size:20px;">A3D</div>
                                @endif
                            </td>
                            <td valign="middle" style="padding:11px 8px;border-bottom:1px solid #e2e8f0;">
                                <div style="font-size:15px;font-weight:800;color:#17324d;">{{ $item->product_name }}</div>
                                <div style="margin-top:4px;font-size:12px;color:#64748b;">{{ $item->product_code }} · {{ $item->quantity }} × RM {{ number_format((float) $item->unit_price, 2) }}</div>
                                @if ((float) $item->customer_discount_amount > 0)
                                    <div style="margin-top:3px;font-size:11px;color:#e7682b;">Discount RM {{ number_format((float) $item->customer_discount_amount, 2) }}</div>
                                @endif
                            </td>
                            <td width="100" align="right" valign="middle" style="padding:11px 0;border-bottom:1px solid #e2e8f0;font-size:15px;font-weight:800;color:#17324d;">RM {{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;background:#f8fafc;border-radius:16px;padding:16px;">
                    <tr><td style="padding:4px 0;color:#64748b;font-size:13px;">Gross total</td><td align="right" style="padding:4px 0;color:#17324d;font-size:13px;font-weight:700;">RM {{ number_format($grossTotal, 2) }}</td></tr>
                    @if ($discountTotal > 0)
                        <tr><td style="padding:4px 0;color:#e7682b;font-size:13px;">Discount</td><td align="right" style="padding:4px 0;color:#e7682b;font-size:13px;font-weight:700;">− RM {{ number_format($discountTotal, 2) }}</td></tr>
                    @endif
                    <tr><td style="padding:10px 0 3px;border-top:1px solid #e2e8f0;color:#17324d;font-size:15px;font-weight:800;">Total paid</td><td align="right" style="padding:10px 0 3px;border-top:1px solid #e2e8f0;color:#e7682b;font-size:22px;font-weight:800;">RM {{ number_format((float) $sale->total_amount, 2) }}</td></tr>
                    <tr><td style="padding:3px 0;color:#64748b;font-size:12px;">Payment method</td><td align="right" style="padding:3px 0;color:#64748b;font-size:12px;font-weight:700;">{{ $paymentLabel }}</td></tr>
                </table>

                @if ($seller)
                    <div style="margin-top:22px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:16px;padding:17px;">
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:1.2px;color:#2563eb;font-weight:800;margin-bottom:11px;">Your seller</div>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="62" valign="middle">
                                    @if ($sellerPictureUrl)
                                        <img src="{{ $sellerPictureUrl }}" width="50" height="50" alt="{{ $seller->agt_name }}" style="display:block;border-radius:50%;object-fit:cover;border:2px solid #ffffff;">
                                    @else
                                        <div style="width:50px;height:50px;line-height:50px;text-align:center;border-radius:50%;background:#17324d;color:#ffffff;font-weight:800;">{{ $seller->initials() }}</div>
                                    @endif
                                </td>
                                <td valign="middle">
                                    <div style="font-size:17px;font-weight:800;color:#17324d;">{{ $seller->agt_name }}</div>
                                    <div style="margin-top:4px;font-size:13px;color:#475569;">{{ $seller->phone_number ?: 'Phone number not provided' }}</div>
                                </td>
                                @if ($seller->whatsappUrl())
                                    <td width="98" align="right" valign="middle"><a href="{{ $seller->whatsappUrl('Hi '.$seller->agt_name.', I have a question about receipt '.$sale->sale_number.'.') }}" style="display:inline-block;background:#16a34a;color:#ffffff;text-decoration:none;font-size:12px;font-weight:700;padding:10px 12px;border-radius:10px;">WhatsApp</a></td>
                                @endif
                            </tr>
                        </table>
                    </div>
                @endif

                <p style="margin:22px 0 0;text-align:center;color:#64748b;font-size:13px;line-height:1.6;">Thank you for choosing Anugerah3D. We hope you enjoy your purchase!</p>
            </td>
        </tr>
        <tr><td style="background:#17324d;padding:18px 26px;text-align:center;color:#cbd5e1;font-size:12px;">Anugerah3D · Personalised ideas, made real.</td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
