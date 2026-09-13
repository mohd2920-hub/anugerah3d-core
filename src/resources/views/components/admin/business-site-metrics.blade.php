@props(['summary'])
<div {{ $attributes->class(['site-metrics']) }}>
    <div><p>Jualan bersih</p><strong>RM {{ number_format((float) $summary->sales_total, 2) }}</strong><small>{{ number_format((int) $summary->sales_count) }} transaksi</small></div>
    <div><p>Hari Operasi</p><strong>{{ number_format((int) $summary->operation_days) }} hari</strong><small>Tarikh sesi dibuka yang berbeza</small></div>
    <div><p>Sesi Perniagaan</p><strong>{{ number_format((int) $summary->operations_count) }} sesi</strong><small>Termasuk sesi sedang beroperasi</small></div>
    <div><p>Unit terjual</p><strong>{{ number_format((int) $summary->items_sold) }}</strong><small>Jumlah unit dijual</small></div>
    <div><p>Modal</p><strong>RM {{ number_format((float) $summary->capital_total, 2) }}</strong><small>Kos item jualan</small></div>
    <div><p>Untung kasar</p><strong @class(['text-emerald-700' => $summary->sales_total >= $summary->capital_total, 'text-red-700' => $summary->sales_total < $summary->capital_total])>RM {{ number_format($summary->sales_total - $summary->capital_total, 2) }}</strong><small>Jualan bersih − modal</small></div>
</div>
