@extends('admin.layouts.app')
@section('title','Gaji Sesi Staf')
@section('page_title','Gaji Sesi Staf')
@section('content')
<div class="space-y-5">
    <a class="font-bold text-blue-700" href="{{ route('admin.salary-management.daily', ['recipient_type'=>'admin']) }}">← Gaji Staf</a>
    <header class="rounded-2xl bg-slate-900 p-6 text-white"><h1 class="text-2xl font-bold">Kehadiran & Draf Gaji Sesi</h1><p class="mt-2 text-sm text-slate-300">Admin memilih staf hadir. Ejen POS tidak dimasukkan secara automatik.</p></header>
    @if(session('success'))<p class="rounded-xl bg-green-50 p-4 text-green-800">{{ session('success') }}</p>@endif
    @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if(!$ready)<p class="rounded-xl bg-amber-50 p-4 text-amber-800">Pratonton pengiraan tersedia. Penyimpanan draf menunggu pengaktifan pangkalan data.</p>@endif
    <p class="rounded-xl bg-blue-50 p-4 text-sm text-blue-800">Simpan draf dahulu. Selepas bayaran sebenar dibuat, admin boleh mengesahkan bayaran dan menghantar slip kepada staf. Pengiraan menggunakan Net Sales sesi selepas diskaun dan mengecualikan jualan Void.</p>
    @if(!($draft->confirmed_at ?? null))
    @adminRoute('admin.salary-management.sessions.preview')
    <form method="post" action="{{ route('admin.salary-management.sessions.preview') }}" class="space-y-4 rounded-2xl border bg-white p-5">@csrf
        <input type="hidden" name="expected_version" value="{{ old('expected_version', $input['expected_version'] ?? 0) }}">
        <div class="grid gap-4 sm:grid-cols-2"><label class="text-sm font-bold">Sesi tapak yang telah ditutup<select name="operation_id" required class="mt-2 block w-full rounded-lg border p-3"><option value="">Pilih sesi</option>@foreach($operations as $operation)<option value="{{ $operation->id }}" @selected((int)old('operation_id', $input['operation_id'] ?? 0)===$operation->id)>#{{ $operation->id }} · {{ $operation->businessSite->site_name }} · {{ $operation->opened_at->timezone('Asia/Kuala_Lumpur')->format('d/m/Y H:i') }}</option>@endforeach</select></label><label class="text-sm font-bold">Peratus gaji sesi (%)<input name="rate" type="number" min="0.01" max="100" step="0.01" value="{{ old('rate', $input['rate'] ?? 40) }}" required class="mt-2 block w-full rounded-lg border p-3"></label></div>
        <h2 class="font-bold">Pilih Staf Hadir</h2><p class="text-sm text-slate-500">Weightage lalai 1.0. Hanya staf bertanda hadir menerima bahagian. Kadar dan weightage ini disimpan untuk sesi ini sahaja.</p>
        <div class="max-h-96 overflow-auto rounded-xl border"><table class="w-full text-left text-sm"><thead class="sticky top-0 bg-slate-50"><tr><th class="p-3">Hadir</th><th class="p-3">Staf</th><th class="p-3">Weightage</th></tr></thead><tbody>@foreach($staff as $person)<tr class="border-t"><td class="p-3"><input type="checkbox" aria-label="{{ $person->name }} hadir" name="staff_ids[]" value="{{ $person->id }}" @checked(in_array($person->id, old('staff_ids', $input['staff_ids'] ?? [])))></td><td class="p-3">{{ $person->name }} @if($person->status !== 'active')<span class="text-xs text-slate-500">(Tidak aktif kini)</span>@endif</td><td class="p-3"><input aria-label="Weightage {{ $person->name }}" type="number" name="weights[{{ $person->id }}]" min="0.01" max="100" step="0.01" value="{{ old('weights.'.$person->id, $input['weights'][$person->id] ?? 1) }}" required class="w-24 rounded-lg border p-2"></td></tr>@endforeach</tbody></table></div>
        <label class="block text-sm font-bold">Catatan kehadiran / sebab perubahan<textarea name="reason" required maxlength="2000" class="mt-2 block w-full rounded-lg border p-3">{{ old('reason', $input['reason'] ?? '') }}</textarea></label>
        <button class="rounded-xl bg-blue-700 px-5 py-3 font-bold text-white">Kira & Semak Draf</button>
    </form>
    @endadminRoute
    @endif
    @php $result = $preview ?? $saved; @endphp
    @if($result)
    <section class="space-y-4 rounded-2xl border bg-white p-5"><h2 class="text-lg font-bold">{{ $preview ? 'Pratonton Pengiraan' : 'Draf Tersimpan' }} · {{ $result['site_name'] }} · {{ $result['work_date'] }}</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">@foreach(['Net Sales'=>'RM '.number_format($result['net_cents']/100,2),'Kadar'=>number_format($result['rate']/100,2).'%','Tabung Gaji'=>'RM '.number_format($result['pool_cents']/100,2)] as $label=>$value)<div class="rounded-xl bg-blue-50 p-4"><p class="text-sm text-slate-600">{{ $label }}</p><p class="mt-2 font-bold">{{ $value }}</p></div>@endforeach</div>
        <table class="w-full text-left text-sm"><thead><tr><th class="p-3">Staf Hadir</th><th class="p-3">Weightage</th><th class="p-3">Bahagian Gaji</th></tr></thead><tbody>@foreach($result['staff'] as $row)<tr class="border-t"><td class="p-3">{{ $row['name'] }}</td><td class="p-3">{{ number_format($row['weight']/100,2) }}</td><td class="p-3 font-bold">RM {{ number_format($row['amount_cents']/100,2) }}</td></tr>@endforeach</tbody></table>
        <p class="text-xs text-slate-500">Baki pembundaran sen diagihkan mengikut baki pecahan terbesar supaya jumlah staf sama dengan tabung gaji.</p>
        @if($result['overlaps'])<div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800"><strong>Semakan bayaran terdahulu diperlukan:</strong>@foreach($result['overlaps'] as $overlap)<p>{{ $overlap['recipient_name'] }} · {{ $overlap['site_name'] }} · Rekod #{{ $overlap['id'] }}</p>@endforeach<p>Draf tidak boleh disimpan sehingga pertindihan disemak.</p></div>@endif
        @if($preview && !$result['overlaps'] && $ready)
        @adminRoute('admin.salary-management.sessions.store')
        <form method="post" action="{{ route('admin.salary-management.sessions.store') }}">@csrf
            @foreach(['operation_id','rate','reason','expected_version'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $input[$field] }}">@endforeach
            <input type="hidden" name="expected_hash" value="{{ $preview['hash'] }}">
            @foreach($preview['staff'] as $row)<input type="hidden" name="staff_ids[]" value="{{ $row['staff_id'] }}"><input type="hidden" name="weights[{{ $row['staff_id'] }}]" value="{{ $row['weight']/100 }}">@endforeach
            <button class="rounded-xl bg-emerald-700 px-5 py-3 font-bold text-white">Simpan Kehadiran & Draf</button>
        </form>@endadminRoute
        @endif
        @if(!$preview && !($draft->confirmed_at ?? null))<p class="text-sm text-slate-500">Angka ini ialah snapshot ketika draf disimpan. Tekan Kira & Semak Draf untuk mengambil jualan terkini.</p>@endif
    </section>
    @endif
    @if($draft && $saved && $confirmationReady)
        @if($draft->confirmed_at)
        <section class="rounded-xl bg-emerald-50 p-5"><h2 class="font-bold">Bayaran Disahkan · Draf Dikunci</h2><p class="mt-2 text-sm">{{ $draft->confirmed_at }}</p><div class="mt-3 space-y-2">@foreach($draftPayments as $payment)<a class="block font-semibold text-blue-700 underline" href="{{ route('admin.salary-management.show', $payment) }}">{{ $payment->slipNumber() }} · {{ $payment->recipient_name }} · RM {{ number_format($payment->amount_cents/100,2) }} · E-mel: {{ $payment->email_sent_at ? 'Dihantar' : ($payment->email_error ? 'Gagal — cuba semula pada slip' : 'Menunggu penghantaran') }}</a>@endforeach</div></section>
        @else
        @adminRoute('admin.salary-management.confirm')
        <form method="post" action="{{ route('admin.salary-management.confirm', $saved['operation_id']) }}" class="space-y-4 rounded-xl border bg-white p-5">@csrf
            <h2 class="text-lg font-bold">Sahkan Bayaran Gaji</h2><p class="text-sm text-slate-600">Jumlah RM {{ number_format($saved['pool_cents']/100,2) }} untuk {{ count($saved['staff']) }} staf. Pastikan setiap staf telah dibayar mengikut draf. Sistem merekod bayaran; tiada pindahan bank automatik. Slip diproses melalui e-mel selepas pengesahan, lazimnya dalam beberapa minit.</p>
            <input type="hidden" name="expected_version" value="{{ $draft->version }}">
            <label class="block text-sm font-bold">Tarikh bayaran sebenar<input type="date" name="paid_date" required min="{{ $saved['work_date'] }}" max="{{ now()->toDateString() }}" value="{{ old('paid_date',now()->toDateString()) }}" class="mt-2 block rounded-lg border p-3"></label>
            <label class="block text-sm font-bold">Rujukan bayaran<input name="reference" required maxlength="150" value="{{ old('reference') }}" class="mt-2 block w-full rounded-lg border p-3"></label>
            <label class="flex gap-2 text-sm"><input type="checkbox" name="confirmed_paid" value="1" required>Saya mengesahkan semua bayaran di atas telah dibuat. Kunci draf dan hantar slip kepada setiap staf melalui e-mel.</label>
            <button class="rounded-xl bg-emerald-700 px-5 py-3 font-bold text-white">Sahkan Bayaran & Hantar Slip</button>
        </form>
        @endadminRoute
        @endif
    @endif
    <section class="rounded-2xl border bg-white p-5"><h2 class="font-bold">Draf Gaji Tersimpan</h2><div class="mt-4 space-y-3">@forelse($drafts ?? [] as $draft)<a href="{{ route('admin.salary-management.sessions', ['operation_id'=>$draft->business_site_operation_id]) }}" class="block rounded-lg border p-3 text-sm font-semibold text-blue-700">Sesi #{{ $draft->business_site_operation_id }} · Draf versi {{ $draft->version }} · Dikemas kini {{ $draft->updated_at }}</a>@empty<p class="text-sm text-slate-500">Belum ada draf gaji sesi.</p>@endforelse</div>@if($drafts)<div class="mt-4">{{ $drafts->links() }}</div>@endif</section>
</div>
@endsection
