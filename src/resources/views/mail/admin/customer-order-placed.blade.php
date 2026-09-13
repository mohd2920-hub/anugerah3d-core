<x-mail::message>
# Pesanan pelanggan baharu: {{ $order->order_number }}
Pelanggan: {{ $order->recipient_name }}

Ejen perujuk: {{ $order->agent->agt_name }} ({{ $order->agent->login_id }})

Jumlah: RM {{ $order->total_amount }}. Pembayaran menunggu pengesahan.
<x-mail::button :url="route('admin.customer-orders.show',$order)">Lihat pesanan</x-mail::button>
</x-mail::message>
