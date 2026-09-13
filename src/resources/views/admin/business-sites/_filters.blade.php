<div class="site-filter-fields">
    <label>Tempoh operasi
        <select name="period" data-site-period>
            @foreach (['today' => 'Hari Ini', 'date' => 'Tarikh Tertentu', 'week' => 'Minggu Ini', 'month' => 'Bulan Ini', 'range' => 'Julat Tarikh', 'all' => 'Semua Sesi'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['period'] ?? 'month') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label data-site-date-field="date">Tarikh operasi<input type="date" name="date" value="{{ $filters['date'] ?? now('Asia/Kuala_Lumpur')->toDateString() }}"></label>
    <label data-site-date-field="range">Dari<input type="date" name="from" value="{{ $filters['from'] ?? now('Asia/Kuala_Lumpur')->startOfMonth()->toDateString() }}"></label>
    <label data-site-date-field="range">Hingga<input type="date" name="to" value="{{ $filters['to'] ?? now('Asia/Kuala_Lumpur')->toDateString() }}"></label>
</div>
@if ($errors->any())
    <div role="alert" class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
@endif
