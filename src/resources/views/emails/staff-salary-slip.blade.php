<!DOCTYPE html>
<html lang="ms"><head><meta charset="utf-8"><title>Slip Gaji Anugerah3D</title></head>
<body style="font-family:Arial,sans-serif;color:#17324d;max-width:760px;margin:24px auto;padding:16px">
<x-brand-logo width="160" :message="$message ?? null" /><h1>Anugerah3D · Slip Gaji</h1>
<p>Salam {{ $payment->recipient_name }}, bayaran gaji anda telah disahkan.</p>
<p><strong>{{ $payment->slipNumber() }}</strong> · Tarikh bayaran: {{ $payment->paid_date->format('d/m/Y') }} · Rujukan: {{ $payment->reference }}</p>
@foreach($periods as $label => $payments)
<h2>{{ $label }}</h2>
<table style="border-collapse:collapse;width:100%" border="1" cellpadding="10"><thead><tr><th>Slip</th><th>Tarikh kerja</th><th>Tapak</th><th>Bayaran (RM)</th></tr></thead><tbody>
@foreach($payments as $row)<tr><td>{{ $row->slipNumber() }}</td><td>{{ $row->work_date->format('d/m/Y') }}</td><td>{{ $row->site_name }}</td><td>{{ number_format($row->amount_cents / 100,2) }}</td></tr>@endforeach
</tbody></table><p><strong>Jumlah: RM {{ number_format($payments->sum('amount_cents') / 100,2) }}</strong></p>
@endforeach
<p>Ringkasan berdasarkan rekod yang tersedia ketika slip ini dikeluarkan. Bayaran terdahulu dalam tempoh yang sama turut disertakan; ini bukan bayaran tambahan.</p>
<p>Simpan lampiran HTML sebagai salinan slip. Untuk PDF, buka lampiran dalam pelayar dan pilih Cetak → Simpan PDF.</p>
</body></html>
