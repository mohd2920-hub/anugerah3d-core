@extends('admin.layouts.app')
@section('title','Salary Management')
@section('page_title','Salary Management')
@section('content')
<div class="space-y-5">
    <header class="rounded-2xl bg-slate-900 p-6 text-white"><h1 class="text-2xl font-bold">Salary Management</h1><p class="mt-2 text-sm text-slate-300">Rekod gaji mulai 1 Januari 2026 · Jumlah sebenar dibayar</p></header>
    @if(session('success'))<p class="rounded-xl bg-green-50 p-4 text-green-800">{{ session('success') }}</p>@endif
    @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <nav class="flex flex-wrap gap-2">@foreach(['index'=>'Overview','daily'=>'Daily Payments','sessions'=>'Gaji Sesi Staf','settings'=>'Salary Settings','payslips'=>'Payslips'] as $key=>$label)<a href="{{ route('admin.salary-management.'.$key, ['recipient_type' => $recipientType, 'month' => $month]) }}" class="rounded-lg border bg-white px-4 py-3 text-sm font-bold text-blue-700">{{ $label }}</a>@endforeach</nav>
    <nav aria-label="Kategori penerima" class="flex gap-3">@foreach(['admin' => 'Gaji Staf', 'agent' => 'Bayaran Ejen'] as $type => $label)<a href="{{ route(request()->route()->getName(), ['recipient_type' => $type, 'month' => $month]) }}" @if($recipientType === $type) aria-current="page" @endif class="rounded-xl border px-5 py-3 font-bold {{ $recipientType === $type ? 'bg-blue-700 text-white' : 'bg-white text-blue-700' }}">{{ $label }}</a>@endforeach</nav>
    <p class="text-sm font-bold text-slate-600">Paparan {{ $categoryLabel }} sahaja — senarai, statistik dan graf diasingkan mengikut kategori.</p>
    <p class="rounded-xl bg-blue-50 p-4 text-sm text-blue-800">Rekod bayaran terdahulu kekal tersedia. Untuk gaji berdasarkan jualan, buka Gaji Sesi Staf, pilih kehadiran dan semak draf sebelum mengesahkan bayaran.</p>
    <form method="get" class="flex flex-wrap items-end gap-3"><input type="hidden" name="recipient_type" value="{{ $recipientType }}"><label class="text-sm font-bold">Bulan bayaran<input type="month" name="month" min="2026-01" value="{{ $month }}" class="ml-3 rounded-lg border p-2"></label><button class="rounded-lg bg-blue-700 px-4 py-2 text-white">Terapkan</button></form>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">@foreach([($categoryLabel.' Direkodkan')=>$recipientCount, 'Draf Belum Disahkan (Semua Tarikh)'=>$recipientType === 'admin' ? 'RM '.number_format(($pendingDraftCents ?? 0)/100,2) : 'Tidak berkenaan', 'Dibayar Hari Ini'=>'RM '.number_format($paidToday/100,2), 'Dibayar Bulan Dipilih'=>'RM '.number_format($total/100,2)] as $label=>$value)<section class="rounded-xl border bg-white p-5"><h2 class="text-sm text-slate-500">{{ $label }}</h2><p class="mt-3 text-xl font-bold">{{ $value }}</p></section>@endforeach</div>
    @if($tab === 'overview')
    <section class="rounded-2xl border bg-white p-6"><h2 class="font-bold">Pecahan Bayaran {{ $categoryLabel }} · {{ $month }}</h2><div class="mt-5 flex flex-wrap items-center gap-8">
        <svg viewBox="0 0 120 120" class="h-56 w-56" role="img" aria-label="Pecahan bayaran gaji bulanan"><circle cx="60" cy="60" r="45" fill="none" stroke="#e2e8f0" stroke-width="18"/>
        @php $offset = 0; $colors = ['#2563eb','#059669','#d97706','#7c3aed','#db2777','#0891b2']; @endphp
        @foreach($breakdown as $row)
            @php $portion = $total > 0 ? $row->amount_cents / $total * 100 : 0; @endphp
            <circle cx="60" cy="60" r="45" fill="none" stroke="{{ $colors[$loop->index % count($colors)] }}" stroke-width="18" pathLength="100" stroke-dasharray="{{ $portion }} {{ 100-$portion }}" stroke-dashoffset="{{ -$offset }}" transform="rotate(-90 60 60)"><title>{{ $row->recipient_name }}: RM {{ number_format($row->amount_cents/100,2) }}</title></circle>
            @php $offset += $portion; @endphp
        @endforeach</svg>
        <ul class="space-y-3 text-sm">@forelse($breakdown as $row)<li><span style="color:{{ $colors[$loop->index % count($colors)] }}">●</span> {{ $row->recipient_name }} · <strong>RM {{ number_format($row->amount_cents/100,2) }}</strong></li>@empty<li class="text-slate-500">Belum ada data bayaran untuk bulan ini.</li>@endforelse</ul>
    </div></section>
    @endif
    @if($tab === 'daily')
    @adminRoute('admin.salary-management.store')
    <details class="rounded-2xl border bg-white p-5" @if($errors->any()) open @endif><summary class="cursor-pointer font-bold text-blue-700">+ Rekod Bayaran Terdahulu</summary>
    <form method="post" action="{{ route('admin.salary-management.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 sm:grid-cols-2">@csrf
        <input type="hidden" name="recipient_type" value="{{ $recipientType }}">
        <input type="hidden" name="submission_token" value="{{ old('submission_token', (string) \Illuminate\Support\Str::uuid()) }}">
        <label class="text-sm font-semibold">{{ $categoryLabel }}<select name="recipient_key" required class="mt-2 block w-full rounded-lg border p-3"><option value="">Pilih {{ $categoryLabel }}</option>@foreach($recipients as $key=>$name)<option value="{{ $key }}" @selected(old('recipient_key')===$key)>{{ $name }}</option>@endforeach</select></label>
        <label class="text-sm font-semibold">Tapak / sesi kerja<input name="site_name" value="{{ old('site_name') }}" required maxlength="150" class="mt-2 block w-full rounded-lg border p-3"></label>
        <label class="text-sm font-semibold">Tarikh kerja<input name="work_date" type="date" min="2026-01-01" max="{{ now()->toDateString() }}" value="{{ old('work_date') }}" required class="mt-2 block w-full rounded-lg border p-3"></label>
        <label class="text-sm font-semibold">Tarikh bayaran sebenar<input name="paid_date" type="date" min="2026-01-01" max="{{ now()->toDateString() }}" value="{{ old('paid_date') }}" required class="mt-2 block w-full rounded-lg border p-3"></label>
        <label class="text-sm font-semibold">Jumlah dibayar (RM)<input name="amount" type="number" min="0.01" max="9999999.99" step="0.01" value="{{ old('amount') }}" required class="mt-2 block w-full rounded-lg border p-3"></label>
        <label class="text-sm font-semibold">Rujukan bayaran (pilihan)<input name="reference" value="{{ old('reference') }}" maxlength="150" class="mt-2 block w-full rounded-lg border p-3"></label>
        <label class="text-sm font-semibold sm:col-span-2">Catatan / sebab rekod terdahulu<textarea name="reason" required maxlength="2000" class="mt-2 block w-full rounded-lg border p-3">{{ old('reason') }}</textarea></label>
        <label class="text-sm font-semibold sm:col-span-2">Bukti bayaran (pilihan) · JPG, PNG, WebP, PDF · 5 MB<input name="proof" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="mt-2 block w-full rounded-lg border p-3"></label>
        <label class="flex gap-2 text-sm sm:col-span-2"><input type="checkbox" name="confirmed_paid" value="1" required @checked(old('confirmed_paid'))>Saya mengesahkan jumlah dan tarikh ini ialah bayaran sebenar yang telah dibuat.</label>
        <label class="flex gap-2 text-sm sm:col-span-2"><input type="checkbox" name="separate_payment" value="1" @checked(old('separate_payment'))>Jika ada Weekly Closing dibayar dalam tempoh ini, saya telah menyemak bahawa rekod ini ialah bayaran berasingan. Sebab dinyatakan dalam catatan.</label>
        <p class="text-xs text-slate-500 sm:col-span-2">Rekod disimpan sebagai Sudah Dibayar · Rekod Terdahulu. Tiada wang atau e-mel dihantar. Semak semua maklumat sebelum menyimpan.</p>
        <button class="rounded-lg bg-blue-700 px-4 py-3 font-bold text-white">Simpan Rekod Sudah Dibayar</button>
    </form></details>
    @endadminRoute
    @endif
    @if($tab === 'payslips')
    <form method="get" action="{{ route('admin.salary-management.summary') }}" class="grid gap-3 rounded-xl border bg-white p-5 sm:grid-cols-4"><h2 class="font-bold sm:col-span-4">Ringkasan Mingguan / Bulanan · mengikut tarikh bayaran</h2><select name="recipient_key" required class="rounded-lg border p-3"><option value="">Pilih {{ $categoryLabel }}</option>@foreach($recipients as $key=>$name)<option value="{{ $key }}">{{ $name }}</option>@endforeach</select><label class="text-sm">Dari<input type="date" name="start" min="2026-01-01" required class="block w-full rounded-lg border p-3"></label><label class="text-sm">Hingga<input type="date" name="end" min="2026-01-01" required class="block w-full rounded-lg border p-3"></label><button class="rounded-lg bg-blue-700 p-3 font-bold text-white">Lihat Ringkasan</button></form>
    @endif
    <div class="overflow-x-auto rounded-xl border bg-white"><table class="w-full text-left text-sm"><thead class="bg-slate-50"><tr>@foreach(['Slip','Penerima','Tarikh Kerja','Tarikh Bayaran','Tapak','Jumlah','Status'] as $label)<th class="whitespace-nowrap p-3">{{ $label }}</th>@endforeach</tr></thead><tbody>@forelse($payments as $payment)<tr class="border-t"><td class="p-3"><a class="font-bold text-blue-700" href="{{ route('admin.salary-management.show', $payment) }}">{{ $payment->slipNumber() }}</a></td><td class="p-3">{{ $payment->recipient_name }}</td><td class="p-3">{{ $payment->work_date->format('d/m/Y') }}</td><td class="p-3">{{ $payment->paid_date->format('d/m/Y') }}</td><td class="p-3">{{ $payment->site_name }}</td><td class="whitespace-nowrap p-3">RM {{ number_format($payment->amount_cents/100,2) }}</td><td class="p-3 text-emerald-700">Sudah Dibayar · {{ $payment->staff_salary_draft_id ? 'Gaji Sesi' : 'Rekod Terdahulu' }}</td></tr>@empty<tr><td colspan="7" class="p-8 text-center text-slate-500">Tiada rekod dalam bulan dipilih.</td></tr>@endforelse</tbody></table><div class="p-4">{{ $payments->links() }}</div></div>
</div>
@endsection
