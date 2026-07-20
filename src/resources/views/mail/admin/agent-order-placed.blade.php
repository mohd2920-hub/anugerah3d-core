<x-mail::message>
# New Agent Order

A new order has been placed through the agent platform.

**Order number:** {{ $order->order_number }}  
**Placed at:** {{ $order->placed_at->format('d M Y, h:i A') }}  
**Order status:** {{ Str::headline($order->status) }}  
**Payment status:** {{ Str::headline($order->payment_status) }}

## Agent

**Name:** {{ $order->agent->agt_name }}  
**Login ID:** {{ $order->agent->login_id }}  
**Email:** {{ $order->agent->email }}  
**Phone:** {{ $order->agent->phone_number ?: '-' }}

## Fulfilment and Payment

**Fulfilment:** {{ Str::headline($order->fulfilment_method) }}  
**Recipient:** {{ $order->recipient_name }}  
**Recipient phone:** {{ $order->phone_number }}  
**Delivery address:** {{ $order->delivery_address ?: 'Self pickup at Anugerah3D pickup counter' }}  
**Payment method:** {{ Str::headline($order->payment_method) }}  
**Order notes:** {{ $order->notes ?: '-' }}

<x-mail::table>
| Product | Qty | Selling | Discount | Agent price | Line total |
| :-- | --: | --: | --: | --: | --: |
@foreach ($order->items as $item)
| {{ $item->product_name }} ({{ $item->product_code }}){{ $item->is_preorder ? ' - Pre-order' : '' }} | {{ $item->quantity }} | RM {{ number_format((float) $item->unit_selling_price, 2) }} | {{ number_format((float) $item->discount_percentage, 1) }}% | RM {{ number_format((float) $item->unit_price, 2) }} | RM {{ number_format((float) $item->line_total, 2) }} |
@endforeach
</x-mail::table>

**Total units:** {{ $order->total_units }}  
**Products subtotal:** RM {{ number_format((float) $order->subtotal, 2) }}  
**Delivery fee:** {{ $order->delivery_fee === null ? 'To be confirmed' : 'RM '.number_format((float) $order->delivery_fee, 2) }}  
**Order total:** RM {{ number_format((float) $order->total_amount, 2) }}

This order has also been recorded in the admin activity list.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
