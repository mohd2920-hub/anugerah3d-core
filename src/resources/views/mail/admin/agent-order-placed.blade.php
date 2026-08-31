<x-mail::message>
@php
	$paymentProofUrls = $order->paymentProofUrls();
	$paymentProofCount = count($paymentProofUrls);
	$grossSubtotal = $order->grossSubtotalAmount();
	$discountAmount = $order->discountAmount();
	$effectiveDiscountPercentage = $order->effectiveDiscountPercentage();
	$deliveryFeeLabel = $order->deliveryFeeLabel();
@endphp

<div style="padding:24px 24px 18px;border-radius:24px;background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 46%,#e0f2fe 100%);border:1px solid #bfdbfe;box-shadow:0 18px 40px rgba(59,130,246,.12);margin-bottom:24px;">
	<div style="font-size:12px;letter-spacing:.16em;font-weight:800;color:#1d4ed8;text-transform:uppercase;">Admin Notification</div>
	<div style="margin-top:10px;font-size:28px;line-height:1.15;font-weight:900;color:#0f172a;">New Agent Order</div>
	<div style="margin-top:10px;color:#1e40af;font-size:15px;line-height:1.6;">
		A new order has been placed through the agent platform and is ready for review.
	</div>
</div>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:18px;">
	<tr>
		<td style="padding:0 8px 8px 0;">
			<div style="background:#fff;border:1px solid #bfdbfe;border-radius:18px;padding:14px 16px;box-shadow:0 8px 22px rgba(59,130,246,.08);">
				<div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#1d4ed8;">Order</div>
				<div style="margin-top:6px;font-size:15px;font-weight:800;color:#111827;">{{ $order->order_number }}</div>
			</div>
		</td>
		<td style="padding:0 0 8px 8px;">
			<div style="background:#fff;border:1px solid #c4b5fd;border-radius:18px;padding:14px 16px;box-shadow:0 8px 22px rgba(99,102,241,.08);">
				<div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#6d28d9;">Placed</div>
				<div style="margin-top:6px;font-size:15px;font-weight:800;color:#111827;">{{ $order->placed_at->format('d M Y, h:i A') }}</div>
			</div>
		</td>
	</tr>
	<tr>
		<td style="padding:8px 8px 0 0;">
			<div style="background:#fff;border:1px solid #bbf7d0;border-radius:18px;padding:14px 16px;box-shadow:0 8px 22px rgba(34,197,94,.08);">
				<div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#15803d;">Order status</div>
				<div style="margin-top:6px;font-size:15px;font-weight:800;color:#111827;">{{ Str::headline($order->status) }}</div>
			</div>
		</td>
		<td style="padding:8px 0 0 8px;">
			<div style="background:#fff;border:1px solid #fed7aa;border-radius:18px;padding:14px 16px;box-shadow:0 8px 22px rgba(251,146,60,.08);">
				<div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#b45309;">Payment status</div>
				<div style="margin-top:6px;font-size:15px;font-weight:800;color:#111827;">{{ Str::headline($order->payment_status) }}</div>
			</div>
		</td>
	</tr>
</table>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(15,23,42,.06);margin-bottom:18px;">
	<div style="font-size:16px;font-weight:900;color:#111827;margin-bottom:12px;">Agent Details</div>
	<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Name</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->agent->agt_name }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Login ID</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->agent->login_id }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Email</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->agent->email }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Phone</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->agent->phone_number ?: '-' }}</td>
		</tr>
	</table>
</div>

<div style="background:linear-gradient(135deg,#fff7ed 0%,#ffedd5 100%);border:1px solid #fed7aa;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(251,146,60,.08);margin-bottom:18px;">
	<div style="font-size:16px;font-weight:900;color:#7c2d12;margin-bottom:12px;">Fulfilment and Payment</div>
	<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Fulfilment</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ Str::headline($order->fulfilment_method) }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Recipient</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->recipient_name }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Recipient phone</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->phone_number }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Delivery address</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->delivery_address ?: 'Self pickup at Anugerah3D pickup counter' }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Payment method</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ Str::headline($order->payment_method) }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Payment proof</td>
			<td style="padding:6px 0;text-align:right;font-weight:900;color:#9a3412;">{{ $paymentProofCount }} image{{ $paymentProofCount === 1 ? '' : 's' }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Order notes</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->notes ?: '-' }}</td>
		</tr>
	</table>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(15,23,42,.06);margin-bottom:18px;">
	<div style="font-size:16px;font-weight:900;color:#111827;margin-bottom:12px;">Items</div>
	<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
		<thead>
			<tr>
				<th align="left" style="background:#f8fafc;padding:10px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Product</th>
				<th align="left" style="background:#f8fafc;padding:10px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Characters</th>
				<th align="right" style="background:#f8fafc;padding:10px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Qty</th>
				<th align="right" style="background:#f8fafc;padding:10px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Selling</th>
				<th align="right" style="background:#f8fafc;padding:10px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Discount</th>
				<th align="right" style="background:#f8fafc;padding:10px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Price</th>
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
					<td style="padding:10px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#111827;font-weight:700;">
						<div>{{ $item->product_name }} ({{ $item->product_code }}){{ $item->is_preorder ? ' - Pre-order' : '' }}</div>
						@if ($item->isClicker())
							<div style="margin-top:8px;white-space:nowrap;">
								@foreach (['Casing' => $item->clickerCasingImageUrl(), 'Huruf' => $item->clickerHurufImageUrl()] as $label => $imageUrl)
									@if ($imageUrl)
										<span style="display:inline-block;margin-right:8px;text-align:center;font-size:9px;color:#64748b;text-transform:uppercase;">
											<img src="{{ $imageUrl }}" alt="{{ $label }} selected" width="56" height="56" style="display:block;width:56px;height:56px;border:1px solid #e2e8f0;border-radius:8px;object-fit:cover;margin-bottom:3px;">{{ $label }}
										</span>
									@endif
								@endforeach
							</div>
						@endif
					</td>
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
					<td align="right" style="padding:10px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#0f172a;">RM {{ number_format((float) $item->unit_selling_price, 2) }}</td>
					<td align="right" style="padding:10px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#0f172a;">{{ number_format((float) $item->discount_percentage, 1) }}%</td>
					<td align="right" style="padding:10px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#0f172a;">RM {{ number_format((float) $item->unit_price, 2) }}</td>
					<td align="right" style="padding:10px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#9a3412;font-weight:900;">RM {{ number_format((float) $item->line_total, 2) }}</td>
				</tr>
			@endforeach
		</tbody>
	</table>
</div>

<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:20px;padding:18px 20px;box-shadow:0 10px 24px rgba(15,23,42,.05);margin-bottom:18px;">
	<div style="font-size:16px;font-weight:900;color:#111827;margin-bottom:12px;">Order Totals</div>
	<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Total units</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">{{ $order->total_units }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Gross subtotal</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#111827;">RM {{ number_format($grossSubtotal, 2) }}</td>
		</tr>
		<tr>
			<td style="padding:6px 0;color:#6b7280;font-size:13px;">Eligible discount</td>
			<td style="padding:6px 0;text-align:right;font-weight:700;color:#15803d;">- RM {{ number_format($discountAmount, 2) }} ({{ number_format($effectiveDiscountPercentage, 1) }}%)</td>
		</tr>
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
			<td style="padding:8px 0 0;text-align:right;font-weight:900;color:#1d4ed8;font-size:18px;">RM {{ number_format((float) $order->total_amount, 2) }}</td>
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

<x-mail::button :url="route('admin.orders.show', $order)">
View Order Details
</x-mail::button>

<p style="margin-top:22px;color:#64748b;font-size:13px;line-height:1.7;">
This email is sent automatically to keep the admin team updated with the latest order snapshot, including payment proof images.
</p>

Thanks,<br>
Anugerah3D
</x-mail::message>
