<x-mail::message>
# @switch($updateType)
@case('submitted')
Order Submitted Successfully
@break
@case('processing')
Order Is Now Processing
@break
@case('completed')
Order Completed
@break
@case('cancelled')
Order Cancelled
@break
@case('payment_updated')
Payment Status Updated
@break
@default
Order Update
@endswitch

Hi {{ $order->agent->agt_name }},

@switch($updateType)
@case('submitted')
Your order has been submitted successfully and recorded in our system. The admin team has been notified.
@break
@case('processing')
Your order has passed the stock check and is now being processed.
@break
@case('completed')
Your order has been marked as completed. Thank you for ordering with Anugerah3D.
@break
@case('cancelled')
Your order has been cancelled. Please contact the admin team if you need further assistance.
@break
@case('payment_updated')
The payment status for your order has been updated to **{{ $order->paymentStatusLabel() }}**.
@break
@default
There is a new update for your order.
@endswitch

**Order number:** {{ $order->order_number }}  
**Order status:** {{ $order->statusLabel() }}  
**Payment method:** {{ $order->paymentMethodLabel() }}  
**Payment status:** {{ $order->paymentStatusLabel() }}  
**Fulfilment:** {{ $order->fulfilmentLabel() }}  
**Placed at:** {{ $order->placed_at->format('d M Y, h:i A') }}

<x-mail::table>
| Product | Qty | Unit price | Line total |
| :-- | --: | --: | --: |
@foreach ($order->items as $item)
| {{ $item->product_name }} ({{ $item->product_code }}){{ $item->is_preorder ? ' - Pre-order' : '' }} | {{ $item->quantity }} | RM {{ number_format((float) $item->unit_price, 2) }} | RM {{ number_format((float) $item->line_total, 2) }} |
@endforeach
</x-mail::table>

**Total units:** {{ $order->total_units }}  
**Products subtotal:** RM {{ number_format((float) $order->subtotal, 2) }}  
**Delivery fee:** {{ $order->delivery_fee === null ? 'To be confirmed' : 'RM '.number_format((float) $order->delivery_fee, 2) }}  
**Order total:** RM {{ number_format((float) $order->total_amount, 2) }}

<x-mail::button :url="$historyUrl">
View Order History
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
