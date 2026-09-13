@extends('customer.layout')
@section('heading','Resit Pembelian')
@section('content')
<div class="space-y-4">
<p class="text-lg font-bold">{{ $order->receipt_number }}</p><p class="font-bold text-emerald-700">Bayaran Disahkan</p>
<p>Pesanan: {{ $receipt['order_number'] }}</p><p>Tarikh pengesahan: {{ $receipt['issued_at'] }}</p><p>Pelanggan: {{ $receipt['recipient_name'] }}</p>
@foreach($receipt['items'] as $item)<div class="border-b py-3"><strong>{{ $item['name'] }}</strong>@if($item['characters'])<p>{{ $item['characters'] }}</p>@endif<p>{{ $item['quantity'] }} × RM {{ $item['unit_price'] }} = RM {{ $item['total'] }}</p></div>@endforeach
<p>Jumlah produk: RM {{ $receipt['subtotal'] }}</p><p>Penghantaran: RM {{ number_format($receipt['delivery_fee'],2) }}</p><p class="text-xl font-bold">Jumlah dibayar: RM {{ $receipt['total'] }}</p>
<div class="flex flex-wrap gap-3 print:hidden"><button data-print-customer-receipt type="button" class="rounded-lg bg-blue-700 px-4 py-3 font-bold text-white">Cetak / Simpan PDF</button><a href="{{ route('agent.customer.status',$order->tracking_token) }}" class="rounded-lg border px-4 py-3">Kembali ke pesanan</a></div>
</div>
@endsection
