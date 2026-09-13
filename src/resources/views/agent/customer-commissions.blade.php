@extends('agent.layouts.app')
@section('page_title','Komisen Katalog Pelanggan')
@section('content')
<p class="mb-4 text-sm">Komisen 25% atas nilai produk, tidak termasuk penghantaran. Layak selepas bayaran disahkan dan pesanan dihantar atau selesai.</p>
@forelse($orders as $order)<div class="mb-3 rounded-xl border bg-white p-4"><a href="{{ route('agent.customer.commissions.show', $order->id) }}" class="font-bold text-blue-700 underline decoration-blue-200 underline-offset-4">{{ $order->order_number }} →</a><p>{{ $order->statusLabel() }} · Jualan produk RM {{ $order->subtotal }}</p><p>Komisen RM {{ number_format($order->earnedCommissionCents()/100,2) }} · {{ $order->commissionStatus() }}</p><p>Diselesaikan: RM {{ number_format($order->paidCommissionCents()/100,2) }}</p></div>@empty<p>Belum ada pesanan daripada link anda.</p>@endforelse {{ $orders->links() }}
@endsection
