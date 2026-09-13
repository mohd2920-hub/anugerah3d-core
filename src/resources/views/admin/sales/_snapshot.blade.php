@if (!$snapshot)
    <p class="text-sm text-slate-500">No sale recorded.</p>
@else
    <dl class="space-y-2 text-sm">
        @foreach (['sale_number' => 'Receipt', 'status' => 'Status', 'sales_person' => 'Sales person', 'sold_at' => 'Sale time', 'customer_name' => 'Customer', 'customer_phone' => 'Phone', 'customer_email' => 'Email', 'remark' => 'Remark', 'payment_method' => 'Payment', 'payment_remark' => 'Payment remark'] as $field => $label)
            <div><dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt><dd class="break-words">{{ $snapshot[$field] ?? '—' }}</dd></div>
        @endforeach
    </dl>
    <div class="mt-4 space-y-3 border-y border-slate-200 py-4">
        @foreach ($snapshot['items'] as $item)
            <div class="text-sm"><p class="font-semibold">{{ $item['product_name'] }} ({{ $item['product_code'] }}) × {{ $item['quantity'] }}</p><x-clicker-sale-details :configuration="$item['clicker_configuration'] ?? null" /><p>Unit RM {{ number_format($item['unit_price'], 2) }} · Cost RM {{ number_format($item['unit_cost'], 2) }}</p><p>Discount RM {{ number_format($item['customer_discount_amount'], 2) }} · Total RM {{ number_format($item['line_total'], 2) }}</p></div>
        @endforeach
    </div>
    <dl class="mt-4 space-y-2 text-sm">@foreach (['net_sales' => 'Net Sales', 'net_company' => 'Net Company', 'capital' => 'Capital', 'gross_profit' => 'Gross Profit'] as $field => $label)<div class="flex justify-between gap-3"><dt>{{ $label }}</dt><dd class="font-semibold">RM {{ number_format($snapshot[$field], 2) }}</dd></div>@endforeach</dl>
    @if ($snapshot['status'] === 'Void')<p class="mt-3 text-sm font-semibold text-red-700">Excluded from sales totals. Original receipt values are retained for audit.</p>@endif
@endif
