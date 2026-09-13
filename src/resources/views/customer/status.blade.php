@extends('customer.layout')
@section('heading','Status Pesanan')
@section('content')
<div class="space-y-4"><h2 class="text-lg font-bold">{{ $order->order_number }}</h2><p>{{ $order->statusLabel() }} · Pembayaran: {{ $order->paymentStatusLabel() }}</p>

@foreach($order->items as $item)<div class="rounded-xl border p-4"><strong>{{ $item->product_name }}</strong><p>{{ $item->quantity }} × RM {{ $item->unit_price }} = RM {{ $item->line_total }}</p>@if($item->isClicker())<p>{{ $item->clickerCharactersText() }} · {{ $item->clicker_character_count }} huruf</p>@endif @if($item->is_preorder)<p class="font-bold text-amber-800">Pre-order: anggaran siap 4 hari selepas tempahan disahkan, tidak termasuk penghantaran.</p>@endif</div>@endforeach
<p>Jumlah produk: RM {{ $order->subtotal }}</p><p>Penghantaran: RM {{ number_format($order->deliveryFeeAmount(),2) }}</p><p class="text-xl font-bold">Jumlah: RM {{ $order->total_amount }}</p>
<p>Pihak kami akan membuat pengesahan melalui WhatsApp selepas bayaran dan pesanan ini diterima.</p>
<div class="flex flex-wrap gap-3">
    <form method="get" action="{{ route('agent.customer.status', $order->tracking_token) }}">
        <input type="hidden" name="refresh" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <button type="submit" class="rounded-lg bg-blue-700 px-4 py-3 font-bold text-white">Muat Semula Status</button>
    </form>
    <button type="button" data-copy-order-link="{{ route('agent.customer.status',$order->tracking_token) }}" class="rounded-lg border px-4 py-3 font-bold">Salin Link Pesanan</button>
</div>
<p role="status" class="text-sm font-bold text-emerald-800">Status disemak: {{ now()->timezone('Asia/Kuala_Lumpur')->format('d/m/Y, h:i:s A') }} (waktu Malaysia)</p>
<p class="text-sm text-slate-600">Simpan link peribadi ini untuk menyemak pesanan tanpa log masuk.</p>
<section id="perkembangan-pesanan" class="space-y-3 rounded-xl border p-4">
    <h3 class="font-bold">Perkembangan Pesanan</h3>
    @php
        $steps = ['pending'=>'Diterima','processing'=>'Diproses','ready'=>'Siap',($order->fulfilment_method === 'delivery' ? 'shipped' : 'pickup_ready')=>($order->fulfilment_method === 'delivery' ? 'Dihantar' : 'Sedia Diambil'),'completed'=>'Selesai'];
        $currentStep = array_search($order->status,array_keys($steps),true);
    @endphp
    @if($order->status === 'cancelled')<p class="font-bold text-red-700">Pesanan dibatalkan.</p>@else
    <ol class="space-y-2">@foreach($steps as $status=>$label)<li @class(['rounded-lg px-3 py-2 text-sm','bg-emerald-50 text-emerald-900'=> $currentStep !== false && $loop->index <= $currentStep,'bg-slate-50 text-slate-500'=> $currentStep === false || $loop->index > $currentStep]) @if($status===$order->status) aria-current="step" @endif>{{ $currentStep !== false && $loop->index <= $currentStep ? '✓' : '○' }} {{ $label }} @if($status===$order->status)<strong> · Status semasa</strong>@endif</li>@endforeach</ol>
    @endif
    @if($order->tracking_number)<p>Kurier: <strong>{{ $order->courier }}</strong></p><p>Nombor tracking: <strong class="select-all">{{ $order->tracking_number }}</strong></p>@endif
</section>
<div class="flex flex-wrap items-start gap-3">
@if($order->payment_status === 'paid' && $order->receipt_number)
    <a href="{{ route('agent.customer.receipt',$order->tracking_token) }}" class="inline-flex rounded-lg bg-emerald-700 px-4 py-3 font-bold text-white">Lihat Resit Pembelian</a>
@endif
@if($order->canConfirmReceipt())
<details class="rounded-lg border border-emerald-700 p-3">
    <summary class="cursor-pointer font-bold text-emerald-800">Sahkan Barang Diterima</summary>
    <form method="post" action="{{ route('agent.customer.confirm-receipt', $order->tracking_token) }}" class="mt-3 max-w-md space-y-3">
        @csrf
        <p>Saya mengesahkan barang telah diterima. Selepas pengesahan, pautan pesanan dan resit akan ditamatkan. Sila simpan resit terlebih dahulu.</p>
        <label class="flex items-start gap-2"><input type="checkbox" name="confirmed" value="1" required><span>Saya telah diberi peluang menyimpan resit dan bersetuju menamatkan pautan ini.</span></label>
        <button class="rounded-lg bg-emerald-700 px-4 py-3 font-bold text-white">Ya, Barang Diterima</button>
    </form>
</details>
@endif
</div>
@if($order->payment_status === 'unpaid' && $order->status !== 'cancelled' && ($order->payment_proof_requested || !$order->paymentProofPaths()))
    @if($order->payment_instructions)<p class="rounded-xl bg-amber-50 p-4">{{ $order->payment_instructions }}</p>@endif
    <form method="post" action="{{ route('agent.customer.proof',$order->tracking_token) }}" enctype="multipart/form-data" class="space-y-3 rounded-xl border p-4">@csrf<label class="block font-bold">Kemas kini bukti pembayaran<input class="mt-2 block w-full" type="file" name="proof" accept="image/jpeg,image/png,image/webp" required></label><p class="text-sm">JPG, PNG atau WebP, maksimum 5 MB.</p><button class="rounded-xl bg-blue-700 p-3 font-bold text-white">Hantar bukti kepada admin</button></form>
@endif
</div>
@endsection
