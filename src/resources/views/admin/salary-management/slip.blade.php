@extends('admin.layouts.app')
@section('title','Slip Gaji')
@section('page_title','Slip Gaji')
@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    @if(session('success'))<p class="rounded-lg bg-emerald-50 p-4 text-emerald-800 print:hidden">{{ session('success') }}</p>@endif
    <div class="flex flex-wrap gap-3 print:hidden"><a href="{{ route('admin.salary-management.payslips', ['recipient_type' => str_starts_with($payments->first()?->recipient_key ?? 'admin:', 'agent:') ? 'agent' : 'admin']) }}" class="rounded-lg border bg-white px-4 py-2">Kembali</a><button type="button" data-print-customer-receipt class="rounded-lg bg-blue-700 px-4 py-2 text-white">Cetak / Simpan PDF</button></div>
    <section class="rounded-xl border bg-white p-6"><x-brand-logo width="120" /><h1 class="text-xl font-bold">Anugerah3D · {{ $title }}</h1><p class="mt-2 text-sm text-slate-500">Slip bayaran gaji · Dokumen dikeluarkan {{ now()->timezone('Asia/Kuala_Lumpur')->format('d/m/Y H:i') }}</p>
    @forelse($payments as $payment)
        <div class="mt-5 space-y-2 border-t pt-4 text-sm"><p class="font-bold text-blue-700">{{ str_starts_with($payment->recipient_key, 'agent:') ? 'Bayaran Ejen' : 'Gaji Staf' }}</p><h2 class="font-bold">{{ $payment->slipNumber() }} · {{ $payment->recipient_name }}</h2><p>Tarikh kerja: {{ $payment->work_date->format('d/m/Y') }} · Tapak: {{ $payment->site_name }}</p><p>Bayaran sebenar: {{ $payment->paid_date->format('d/m/Y') }} · <strong>RM {{ number_format($payment->amount_cents/100,2) }}</strong></p><p>Status: Sudah Dibayar · {{ $payment->staff_salary_draft_id ? 'Gaji Sesi' : 'Rekod Terdahulu' }}</p><p>Rujukan: {{ $payment->reference ?: '—' }}</p><p>Direkodkan: {{ $payment->created_at->timezone('Asia/Kuala_Lumpur')->format('d/m/Y H:i') }}</p>@if($payment->staff_salary_draft_id)<div class="rounded-lg bg-slate-50 p-3 print:hidden"><p>E-mel slip: {{ $payment->email_sent_at ? 'Dihantar '.$payment->email_sent_at->format('d/m/Y H:i') : ($payment->email_error ?: 'Menunggu penghantaran') }}</p><p>Penerima: {{ $payment->recipient_email }}</p>@if(!$payment->email_sent_at)@adminRoute('admin.salary-management.email')<form method="post" action="{{ route('admin.salary-management.email', $payment) }}" class="mt-2">@csrf<button class="rounded-lg border bg-white px-4 py-2 font-bold text-blue-700">Cuba Semula E-mel Slip</button></form>@endadminRoute @endif</div>@endif
        @if($payment->proof_path)<a class="inline-block text-blue-700 underline print:hidden" href="{{ route('admin.salary-management.proof',$payment) }}" target="_blank" rel="noopener">Lihat bukti bayaran</a>@endif</div>
    @empty<p class="py-8">Tiada bayaran untuk tempoh ini.</p>@endforelse
    <p class="mt-6 border-t pt-4 text-lg font-bold">Jumlah: RM {{ number_format($payments->sum('amount_cents')/100,2) }}</p><p class="mt-3 text-xs text-slate-500">Ringkasan bayaran yang telah dibuat. Dokumen ini tidak menghasilkan bayaran baharu.</p></section>
</div>
@endsection
