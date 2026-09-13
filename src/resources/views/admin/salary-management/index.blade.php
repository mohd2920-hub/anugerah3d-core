@extends('admin.layouts.app')
@section('title', 'Salary Management | Anugerah3D Admin')
@section('page_title', 'Salary Management')
@section('content')
<div class="space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-slate-900 p-6 text-white">
        <div><p class="text-xs font-bold uppercase tracking-widest text-blue-300">Pengurusan Gaji</p><h1 class="mt-2 text-2xl font-bold">Salary Management</h1><p class="mt-2 text-sm text-slate-300">Bayaran harian, kadar staf dan slip gaji dalam satu tempat.</p></div>
        <span class="rounded-full border border-amber-300/30 bg-amber-300/10 px-4 py-2 text-sm font-bold text-amber-200">Pratonton UI · Tiada data sebenar</span>
    </header>
    <p class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">Modul ini belum disambungkan kepada jualan, kehadiran atau pembayaran. Semua jumlah masih kosong. Simpan, pengesahan bayaran dan penghantaran e-mel belum diaktifkan.</p>
    <nav aria-label="Bahagian pengurusan gaji" class="flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-white p-2">
        @foreach(['overview' => ['Overview', 'index'], 'daily' => ['Daily Payments', 'daily'], 'settings' => ['Salary Settings', 'settings'], 'payslips' => ['Payslips', 'payslips']] as $key => [$label, $route])
            <a href="{{ route('admin.salary-management.'.$route) }}" @if($tab === $key) aria-current="page" @endif class="rounded-lg px-4 py-3 text-sm font-bold {{ $tab === $key ? 'bg-blue-700 text-white' : 'text-slate-600 hover:bg-slate-50' }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if($tab === 'overview')
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach(['Jumlah Staf / Ejen', 'Menunggu Bayaran', 'Dibayar Hari Ini', 'Jumlah Dibayar Bulan Ini'] as $label)
        <section class="rounded-2xl border border-slate-200 border-t-4 border-t-blue-500 bg-white p-5 shadow-sm"><h2 class="text-sm font-semibold text-slate-600">{{ $label }}</h2><p class="mt-3 text-3xl font-bold text-slate-900">—</p><p class="mt-2 text-xs text-slate-400">Belum disambungkan kepada data</p></section>
        @endforeach
    </div>
    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6"><h2 class="text-lg font-bold">Pecahan Gaji Bulanan</h2><p class="mt-1 text-sm text-slate-500">Mengikut staf / ejen · bulan akan dipilih selepas pengaktifan</p>
            <div class="relative mx-auto my-8 h-56 w-56"><svg viewBox="0 0 100 100" role="img" aria-label="Graf pai gaji kosong" class="h-full w-full"><circle cx="50" cy="50" r="39" fill="none" stroke="#e2e8f0" stroke-width="15"/></svg><div class="absolute inset-0 flex flex-col items-center justify-center"><span class="text-3xl font-bold text-slate-400">—</span><span class="mt-2 text-sm text-slate-500">Belum ada data</span></div></div>
            <p class="text-center text-sm text-slate-500">Pecahan graf akan muncul apabila bayaran gaji direkodkan.</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6"><h2 class="text-lg font-bold">Aliran Bayaran Harian</h2><ol class="mt-5 space-y-4">@foreach(['Sesi tapak selesai dan kehadiran disemak', 'Draf gaji dikira mengikut kadar dan weightage', 'Admin mengesahkan pengiraan', 'Bayaran dibuat dan bukti direkodkan', 'Slip dijana dan e-mel kepada staf'] as $step)<li class="flex items-center gap-3 text-sm text-slate-700"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-50 font-bold text-blue-700">{{ $loop->iteration }}</span>{{ $step }}</li>@endforeach</ol><p class="mt-6 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Pengiraan disahkan dan bayaran selesai ialah dua status berbeza.</p></section>
    </div>
    @elseif($tab === 'daily')
    <section class="rounded-2xl border border-slate-200 bg-white p-6"><h2 class="text-lg font-bold">Bayaran Gaji Harian</h2><p class="mt-1 text-sm text-slate-500">Semak sesi, kehadiran dan pecahan gaji sebelum pengesahan.</p>
        <fieldset disabled class="mt-5 grid gap-3 sm:grid-cols-3"><label class="text-sm font-semibold">Tarikh<input type="date" class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-50 p-3"></label><label class="text-sm font-semibold">Business Site<select class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-50 p-3"><option>Pilih tapak</option></select></label><label class="text-sm font-semibold">Status<select class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-50 p-3"><option>Semua status</option><option>Draf</option><option>Pengiraan disahkan</option><option>Sudah dibayar</option></select></label></fieldset>
        <div class="mt-6 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr>@foreach(['Tarikh / Sesi', 'Staf / Ejen', 'Kehadiran', 'Weightage', 'Gaji', 'Status', 'Tindakan'] as $label)<th class="whitespace-nowrap p-3">{{ $label }}</th>@endforeach</tr></thead><tbody><tr><td colspan="7" class="p-12 text-center text-slate-500">Tiada rekod gaji. Data belum disambungkan.</td></tr></tbody></table></div>
        <div class="mt-5 flex flex-wrap gap-3">@foreach(['Sahkan Pengiraan', 'Rekod Bayaran'] as $label)<button disabled class="cursor-not-allowed rounded-lg bg-slate-100 px-4 py-3 text-sm font-bold text-slate-400">{{ $label }}</button>@endforeach</div>
    </section>
    @elseif($tab === 'settings')
    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6"><h2 class="text-lg font-bold">Tetapan Kadar Gaji</h2><fieldset disabled class="mt-5 space-y-4"><label class="block text-sm font-semibold">Peratus jualan sesi (%)<input type="number" value="40" class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-50 p-3"></label><label class="block text-sm font-semibold">Weightage lalai staf<input type="number" value="1.0" step="0.1" class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-50 p-3"></label><button class="cursor-not-allowed rounded-lg bg-slate-100 px-4 py-3 text-sm font-bold text-slate-400">Simpan Tetapan</button></fieldset><p class="mt-3 text-xs text-slate-500">40% dan 1.0 ialah cadangan nilai lalai. Belum disimpan atau digunakan dalam pengiraan sebenar.</p></section>
        <section class="rounded-2xl border border-blue-100 bg-blue-50 p-6"><h2 class="text-lg font-bold text-blue-900">Kaedah Pengiraan Dicadangkan</h2><div class="mt-5 space-y-4 text-sm leading-7 text-blue-900"><p><strong>Tabung gaji harian</strong><br>Net Sales sesi × peratus gaji</p><p><strong>Gaji setiap staf</strong><br>Tabung gaji × (weightage staf ÷ jumlah weightage staf hadir)</p><p>Hanya staf yang hadir menerima bahagian. Jika tiada kehadiran, pengiraan ditahan untuk semakan.</p></div></section>
    </div>
    <section class="rounded-2xl border border-slate-200 bg-white p-6"><h2 class="text-lg font-bold">Weightage Staf / Ejen</h2><div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50"><tr><th class="p-3">Nama</th><th class="p-3">Weightage</th><th class="p-3">Tarikh Berkuat Kuasa</th><th class="p-3">Tindakan</th></tr></thead><tbody><tr><td colspan="4" class="p-10 text-center text-slate-500">Senarai staf belum disambungkan.</td></tr></tbody></table></div></section>
    @else
    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6"><h2 class="text-lg font-bold">Slip & Ringkasan Gaji</h2><fieldset disabled class="mt-5 space-y-4"><label class="block text-sm font-semibold">Jenis slip<select class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-50 p-3"><option>Harian</option><option>Mingguan</option><option>Bulanan</option></select></label><label class="block text-sm font-semibold">Staf / Ejen<select class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-50 p-3"><option>Pilih staf / ejen</option></select></label><label class="block text-sm font-semibold">Tarikh<input type="date" class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-50 p-3"></label><div class="flex flex-wrap gap-3"><button class="rounded-lg bg-slate-100 px-4 py-3 text-sm font-bold text-slate-400">Muat Turun PDF</button><button class="rounded-lg bg-slate-100 px-4 py-3 text-sm font-bold text-slate-400">Hantar E-mel</button></div></fieldset><p class="mt-4 text-sm text-slate-500">Slip mingguan dan bulanan merumuskan bayaran harian, tanpa menghasilkan bayaran baharu.</p></section>
        <section class="rounded-2xl border border-dashed border-slate-300 bg-white p-6"><div class="flex justify-between border-b pb-4"><h2 class="font-bold">Anugerah3D · Slip Gaji</h2><span class="text-xs font-bold text-amber-700">PRATONTON</span></div><dl class="mt-5 space-y-4 text-sm">@foreach(['Nombor slip', 'Nama staf / ejen', 'Tempoh', 'Business Site / Sesi', 'Kehadiran', 'Weightage', 'Kadar gaji', 'Jumlah dibayar', 'Tarikh bayaran', 'Rujukan bayaran'] as $label)<div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $label }}</dt><dd class="font-bold">—</dd></div>@endforeach</dl><p class="mt-6 border-t pt-4 text-xs text-slate-400">Bukan slip rasmi. Slip sebenar hanya dijana selepas bayaran disahkan.</p></section>
    </div>
    @endif
</div>
@endsection
