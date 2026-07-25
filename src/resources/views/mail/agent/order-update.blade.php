<x-mail::message>
@php
	$paymentProofUrls = $order->paymentProofUrls();
	$paymentProofCount = count($paymentProofUrls);
@endphp

<div style="padding:24px 24px 18px;border-radius:24px;background:linear-gradient(135deg,#fff7ed 0%,#ffedd5 48%,#fef3c7 100%);border:1px solid #fed7aa;box-shadow:0 18px 40px rgba(251,146,60,.14);margin-bottom:24px;">
	<div style="font-size:12px;letter-spacing:.16em;font-weight:800;color:#c2410c;text-transform:uppercase;">Order Update</div>
	<div style="margin-top:10px;font-size:28px;line-height:1.15;font-weight:900;color:#7c2d12;">
		@switch($updateType)
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
	</div>
	<div style="margin-top:10px;color:#9a3412;font-size:15px;line-height:1.6;">
		Hi {{ $order->agent->agt_name }}, this is the latest update for your order <strong>{{ $order->order_number }}</strong>.
	</div>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:18px;">
	<tr>
		<td style="padding:0 8px 8px 0;">
			<div style="background:#fff;border:1px solid #fde68a;border-radius:18px;padding:14px 16px;box-shadow:0 8px 22px rgba(251,191,36,.08);">
				<div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#b45309;">Order</div>
				<div style="margin-top:6px;font-size:15px;font-weight:800;color:#111827;">{{ $order->order_number }}</div>
			</div>
		</td>
		<td style="padding:0 0 8px 8px;">
			<div style="background:#fff;border:1px solid #fed7aa;border-radius:18px;padding:14px 16px;box-shadow:0 8px 22px rgba(251,146,60,.08);">
				<div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#b45309;">Status</div>
				<div style="margin-top:6px;font-size:15px;font-weight:800;color:#111827;">{{ $order->statusLabel() }}</div>
			</div>
		</td>
	</tr>
	<tr>
		<td style="padding:8px 8px 0 0;">
			<div style="background:#fff;border:1px solid #bbf7d0;border-radius:18px;padding:14px 16px;box-shadow:0 8px 22px rgba(34,197,94,.08);">
				<div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#15803d;">Payment</div>
				<div style="margin-top:6px;font-size:15px;font-weight:800;color:#111827;">{{ $order->paymentStatusLabel() }}</div>
			</div>
		</td>
		<td style="padding:8px 0 0 8px;">
			<div style="background:#fff;border:1px solid #dbeafe;border-radius:18px;padding:14px 16px;box-shadow:0 8px 22px rgba(59,130,246,.08);">
				<div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#2563eb;">Fulfilment</div>
				<div style="margin-top:6px;font-size:15px;font-weight:800;color:#111827;">{{ $order->fulfilmentLabel() }}</div>
			</div>
		</td>
	</tr>
</table>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(15,23,42,.06);margin-bottom:18px;">
	<div style="font-size:16px;font-weight:900;color:#111827;margin-bottom:12px;">Order Summary</div>
	<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Placed at</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->placed_at->format('d M Y, h:i A') }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Payment method</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->paymentMethodLabel() }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Total units</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->total_units }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Order total</td>
			<td style="padding:6px 0;text-align:right;font-weight:900;color:#9a3412;font-size:16px;">RM {{ number_format((float) $order->total_amount, 2) }}</td>
		</tr>
	</table>
</div>

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

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(15,23,42,.06);margin:18px 0;">
	<div style="font-size:16px;font-weight:900;color:#111827;margin-bottom:12px;">Items</div>
	<x-mail::table>
| Product | Qty | Unit price | Line total |
| :-- | --: | --: | --: |
@foreach ($order->items as $item)
| {{ $item->product_name }} ({{ $item->product_code }}){{ $item->is_preorder ? ' - Pre-order' : '' }} | {{ $item->quantity }} | RM {{ number_format((float) $item->unit_price, 2) }} | RM {{ number_format((float) $item->line_total, 2) }} |
@endforeach
	</x-mail::table>
</div>

<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:20px;padding:18px 20px;box-shadow:0 10px 24px rgba(15,23,42,.05);margin-bottom:18px;">
	<div style="font-size:16px;font-weight:900;color:#111827;margin-bottom:12px;">Totals</div>
	<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Products subtotal</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">RM {{ number_format((float) $order->subtotal, 2) }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Delivery fee</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->delivery_fee === null ? 'To be confirmed' : 'RM '.number_format((float) $order->delivery_fee, 2) }}</td>
		</tr>
		<tr>
			<td style="padding:8px 0 0;color:#6b7280;font-size:13px;">Order total</td>
			<td style="padding:8px 0 0;text-align:right;font-weight:900;color:#9a3412;font-size:18px;">RM {{ number_format((float) $order->total_amount, 2) }}</td>
		</tr>
	</table>
</div>

@if ($paymentProofCount > 0)
<div style="background:#fff7ed;border:1px solid #fdba74;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(251,146,60,.08);margin-bottom:18px;">
	<div style="font-size:16px;font-weight:900;color:#7c2d12;margin-bottom:12px;">Payment Proof</div>
	<div style="font-size:13px;color:#9a3412;margin-bottom:12px;">{{ $paymentProofCount }} uploaded image{{ $paymentProofCount === 1 ? '' : 's' }} attached below.</div>
	<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
		<tr>
			@foreach ($paymentProofUrls as $proofUrl)
				<td style="padding:0 6px 12px 0;vertical-align:top;width:50%;">
					<a href="{{ $proofUrl }}" target="_blank" rel="noopener" style="text-decoration:none;">
						<img src="{{ $proofUrl }}" alt="Payment proof" style="display:block;width:100%;border-radius:16px;border:1px solid #fed7aa;object-fit:cover;box-shadow:0 10px 24px rgba(251,146,60,.12);">
					</a>
				</td>
				@if ($loop->iteration % 2 === 0 && ! $loop->last)
					</tr><tr>
				@endif
			@endforeach
		</tr>
	</table>
</div>
@endif

<x-mail::button :url="$historyUrl">
View Order History
</x-mail::button>

<p style="margin-top:22px;color:#6b7280;font-size:13px;line-height:1.7;">
If anything about this order needs attention, you can reply to this email or check the order history for the latest update.
</p>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
