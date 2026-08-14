<x-mail::message>
@php
	$paymentProofUrls = $order->paymentProofUrls();
	$paymentProofCount = count($paymentProofUrls);
	$deliveryFeeLabel = $order->fulfilment_method === 'delivery' ? 'RM ' . number_format((float) ($order->delivery_fee ?? 6), 2) : 'RM 0.00';
	$locationLabel = $order->fulfilment_method === 'delivery' ? 'Delivery address' : 'Pickup location';
	$locationValue = $order->delivery_address ?: 'Anugerah3D pickup counter';
@endphp

<div style="padding:24px 24px 18px;border-radius:24px;background:linear-gradient(135deg,#fff7ed 0%,#ffedd5 48%,#fef3c7 100%);border:1px solid #fed7aa;box-shadow:0 18px 40px rgba(251,146,60,.14);margin-bottom:24px;">
	<div style="font-size:12px;letter-spacing:.16em;font-weight:800;color:#c2410c;text-transform:uppercase;">Order Update</div>
	<div style="margin-top:10px;font-size:28px;line-height:1.15;font-weight:900;color:#7c2d12;">
		@switch($updateType)
		@case('submitted')
		Order Confirmed
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
		Hi {{ $order->agent->agt_name }}, thank you for ordering with Anugerah3D. Here is your latest order summary for <strong>{{ $order->order_number }}</strong>.
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
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Recipient</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->recipient_name }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Phone</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->phone_number }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Payment method</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->paymentMethodLabel() }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">{{ $locationLabel }}</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $locationValue }}</td>
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
Your order has been received successfully and our team has already been notified. We will verify stock, payment, and fulfilment details before moving it to processing.
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
	<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
		<thead>
			<tr>
				<th align="left" style="background:#f8fafc;padding:10px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Product</th>
				<th align="left" style="background:#f8fafc;padding:10px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Characters</th>
				<th align="right" style="background:#f8fafc;padding:10px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Qty</th>
				<th align="right" style="background:#f8fafc;padding:10px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Unit price</th>
				<th align="right" style="background:#f8fafc;padding:10px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Line total</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($order->items as $item)
				@php
					$characters = $item->isClicker()
						? collect($item->clicker_characters ?? [])
							->map(fn (mixed $character): string => strtoupper(trim((string) $character)))
							->filter()
							->values()
						: collect();
				@endphp
				<tr>
					<td style="padding:10px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#111827;font-weight:700;">{{ $item->product_name }} ({{ $item->product_code }}){{ $item->is_preorder ? ' - Pre-order' : '' }}</td>
					<td style="padding:10px;border-bottom:1px solid #f1f5f9;">
						@if ($characters->isNotEmpty())
							<div style="margin-bottom:5px;"><span style="display:inline-block;border-radius:999px;background:#e2e8f0;padding:2px 8px;color:#334155;font-size:10px;font-weight:700;text-transform:uppercase;">Characters ({{ (int) ($item->clicker_character_count ?? $characters->count()) }})</span></div>
							<div style="white-space:nowrap;">
								@foreach ($characters as $character)
									<span style="display:inline-block;width:18px;height:18px;line-height:18px;text-align:center;border-radius:6px;border:1px solid #fdba74;background:#fff7ed;color:#c2410c;font-size:10px;font-weight:900;margin-right:3px;">{{ $character }}</span>
								@endforeach
							</div>
						@else
							<span style="font-size:12px;color:#94a3b8;">-</span>
						@endif
					</td>
					<td align="right" style="padding:10px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#0f172a;font-weight:700;">{{ $item->quantity }}</td>
					<td align="right" style="padding:10px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#0f172a;">RM {{ number_format((float) $item->unit_price, 2) }}</td>
					<td align="right" style="padding:10px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#9a3412;font-weight:900;">RM {{ number_format((float) $item->line_total, 2) }}</td>
				</tr>
			@endforeach
		</tbody>
	</table>
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
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $deliveryFeeLabel }}</td>
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
Track Order History
</x-mail::button>

<p style="margin-top:22px;color:#6b7280;font-size:13px;line-height:1.7;">
If anything about this order needs attention, you can reply to this email or check the order history for the latest update.
</p>

Thanks,<br>
Anugerah3D
</x-mail::message>
