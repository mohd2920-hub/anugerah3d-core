@extends('agent.layouts.app')
@section('page_title', 'Transaksi Bayaran Komisen')
@section('back_url', route('agent.customer.commissions'))
@section('content')
<div class="space-y-4">
    <section class="rounded-xl border bg-white p-4">
        <h2 class="text-lg font-bold">{{ $order->order_number }}</h2>
        <p class="mt-2 text-sm">Bayaran komisen daripada Anugerah3D</p>
        <p class="mt-2 font-semibold">{{ $order->commissionStatus() }} · Komisen RM {{ number_format($order->earnedCommissionCents()/100, 2) }}</p>
        <p class="text-sm text-slate-600">Diselesaikan: RM {{ number_format($order->paidCommissionCents()/100, 2) }}</p>
    </section>
    @forelse($entries as $entry)
        <article class="space-y-3 rounded-xl border bg-white p-4">
            <h3 class="font-bold">{{ (float)$entry->amount < 0 ? 'Pelarasan komisen terdahulu' : 'Transaksi bayaran komisen' }}</h3>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-slate-500">Tarikh direkodkan</dt><dd>{{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('d/m/Y H:i') }}</dd></div>
                <div><dt class="text-slate-500">Rujukan transaksi</dt><dd class="break-words font-semibold">{{ $entry->reference ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Komisen diselesaikan / pelarasan</dt><dd>RM {{ number_format((float)$entry->amount, 2) }}</dd></div>
                <div><dt class="text-slate-500">Jumlah wang dibayar</dt><dd class="text-lg font-bold text-emerald-700">RM {{ number_format((float)$entry->cash_amount, 2) }}</dd></div>
            </dl>
            @if((float)$entry->amount > (float)$entry->cash_amount)
                <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">RM {{ number_format((float)$entry->amount - (float)$entry->cash_amount, 2) }} digunakan untuk pelarasan komisen terdahulu.</p>
            @endif
            @if($entry->proof_path)
                @if(strtolower(pathinfo($entry->proof_path, PATHINFO_EXTENSION)) !== 'pdf')
                    <a href="{{ route('agent.customer.commissions.proof', [$order->id, $entry->id]) }}" target="_blank" rel="noopener"><img src="{{ route('agent.customer.commissions.proof', [$order->id, $entry->id]) }}" alt="Bukti bayaran komisen daripada Anugerah3D" class="max-h-96 w-full rounded-lg border object-contain" loading="lazy"></a>
                @endif
                <a href="{{ route('agent.customer.commissions.proof', [$order->id, $entry->id]) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white">Lihat bukti bayaran</a>
            @else
                <p class="text-sm text-slate-500">Tiada lampiran bukti untuk transaksi ini.</p>
            @endif
        </article>
    @empty
        <p class="rounded-xl border bg-white p-4 text-sm text-slate-600">Belum ada transaksi bayaran komisen untuk pesanan ini.</p>
    @endforelse
</div>
@endsection
