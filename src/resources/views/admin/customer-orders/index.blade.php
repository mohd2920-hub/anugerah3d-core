@extends('admin.layouts.app')
@section('title','Customer Orders')
@section('content')
<div class="space-y-4"><h1 class="text-xl font-bold">Customer Orders &amp; Commissions</h1>
<form><input name="search" value="{{ $search }}" placeholder="Cari pesanan / pelanggan / ejen" class="rounded border p-3"><button class="rounded bg-blue-700 p-3 text-white">Cari</button></form>
@foreach($orders as $order)
<a class="block rounded-xl border bg-white p-4" href="{{ route('admin.customer-orders.show',$order) }}"><strong>{{ $order->order_number }}</strong> · {{ $order->recipient_name }}<br>Ejen: {{ $order->agent->agt_name }} · RM {{ $order->total_amount }} · {{ $order->statusLabel() }}<br>Komisen: {{ $order->commissionStatus() }} · RM {{ number_format($order->earnedCommissionCents()/100,2) }}</a>
@endforeach {{ $orders->links() }}</div>
@endsection
