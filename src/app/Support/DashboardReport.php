<?php

namespace App\Support;

use App\Models\AdminUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardReport
{
    public const Channels = ['pos' => 'POS', 'orders' => 'Pesanan Ejen', 'customer' => 'Pembelian Pelanggan'];

    public function channels(AdminUser $user): array
    {
        return array_filter(self::Channels, fn (string $key): bool => AdminAccess::allows($user, match ($key) {
            'pos' => 'sales.view', 'orders' => 'orders.view', 'customer' => 'customer-orders.view',
        }), ARRAY_FILTER_USE_KEY);
    }

    /** @return array{CarbonImmutable, CarbonImmutable, CarbonImmutable, CarbonImmutable} */
    public function ranges(array $filters): array
    {
        $today = CarbonImmutable::now();
        if (isset($filters['start_date'], $filters['end_date'])) {
            $start = CarbonImmutable::parse($filters['start_date'])->startOfDay();
            $end = CarbonImmutable::parse($filters['end_date'])->endOfDay()->min($today);
            $days = (int) $start->diffInDays($end->startOfDay()) + 1;
            $previousStart = ($filters['comparison'] ?? 'previous') === 'year' ? $start->subYearNoOverflow() : $start->subDays($days);
            $previousEnd = ($filters['comparison'] ?? 'previous') === 'year'
                ? $end->subYearNoOverflow() : $start->subDay()->setTime($end->hour, $end->minute, $end->second);

            return [$start, $end, $previousStart, $previousEnd];
        }
        $start = CarbonImmutable::create((int) ($filters['year'] ?? $today->year), (int) ($filters['month'] ?? 1), 1)->startOfDay();
        $end = isset($filters['month']) ? $start->endOfMonth() : $start->endOfYear();
        $end = $end->min($today);
        $previousStart = ! isset($filters['month']) || ($filters['comparison'] ?? 'previous') === 'year'
            ? $start->subYearNoOverflow() : $start->subMonthNoOverflow();
        $previousEnd = isset($filters['month']) ? $previousStart->endOfMonth() : $previousStart->endOfYear();
        if ($end->lt(isset($filters['month']) ? $start->endOfMonth() : $start->endOfYear())) {
            $previousEnd = isset($filters['month'])
                ? $previousStart->day(min($end->day, $previousStart->daysInMonth))->setTime($end->hour, $end->minute, $end->second)
                : $previousStart->month($end->month)->day(min($end->day, $previousStart->month($end->month)->daysInMonth))->setTime($end->hour, $end->minute, $end->second);
        }

        return [$start, $end, $previousStart, $previousEnd];
    }

    public function data(AdminUser $user, array $filters): array
    {
        $allowed = $this->channels($user);
        $channel = $filters['channel'] ?? 'all';
        abort_if($channel !== 'all' && ! isset($allowed[$channel]), 403);
        abort_if(! empty($filters['site']) && ! isset($allowed['pos']), 403);
        [$start, $end, $previousStart, $previousEnd] = $this->ranges($filters);
        $query = $this->entries($user, $filters, $start, $end);
        $summary = $this->totals(clone $query);
        $agentOrders = (clone $query)->where('channel', 'orders')->where('kind', 'sale')->select('id');
        $summary['agent_discount_potential'] = round((float) DB::table('order_items as agent_items')
            ->whereIn('agent_items.order_id', $agentOrders)
            ->selectRaw('COALESCE(SUM(GREATEST(0, agent_items.unit_selling_price * agent_items.quantity - agent_items.line_total)),0) as amount')
            ->value('amount'), 2);
        $previous = $this->totals($this->entries($user, $filters, $previousStart, $previousEnd));
        $group = isset($filters['month']) ? 'DAY(date)' : 'MONTH(date)';
        $grouped = (clone $query)->selectRaw("{$group} as period, SUM(sales) as sales, SUM(cost) as cost")
            ->groupByRaw($group)->get()->keyBy('period');
        $count = isset($filters['month']) ? $start->daysInMonth : 12;
        $series = collect(range(1, $count))->map(function (int $index) use ($filters, $start, $end, $grouped): array {
            $date = isset($filters['month']) ? $start->day($index) : $start->month($index);
            $row = $grouped->get($index);

            return ['period' => $index, 'future' => $date->gt($end), 'sales' => round((float) ($row->sales ?? 0), 2), 'cost' => round((float) ($row->cost ?? 0), 2)];
        })->all();
        $granularity = isset($filters['month']) ? 'day' : 'month';
        if (isset($filters['start_date'])) {
            $granularity = $start->format('Y-m') === $end->format('Y-m') ? 'day' : 'month';
            $format = $granularity === 'day' ? '%Y-%m-%d' : '%Y-%m';
            $grouped = (clone $query)->selectRaw("DATE_FORMAT(date, '{$format}') as bucket, SUM(sales) as sales, SUM(cost) as cost")
                ->groupBy('bucket')->get()->keyBy('bucket');
            $series = [];
            $cursor = $granularity === 'day' ? $start : $start->startOfMonth();
            while ($cursor->lte($end)) {
                $key = $cursor->format($granularity === 'day' ? 'Y-m-d' : 'Y-m');
                $row = $grouped->get($key);
                $bucketEnd = $granularity === 'day' ? $cursor->endOfDay() : $cursor->endOfMonth();
                $series[] = [
                    'period' => count($series) + 1, 'future' => false,
                    'label' => $granularity === 'day' ? $cursor->format('d/m') : $cursor->format('m/Y'),
                    'start' => $cursor->max($start)->toDateString(), 'end' => $bucketEnd->min($end)->toDateString(),
                    'sales' => round((float) ($row->sales ?? 0), 2), 'cost' => round((float) ($row->cost ?? 0), 2),
                ];
                $cursor = $granularity === 'day' ? $cursor->addDay() : $cursor->addMonth();
            }
        }
        $mix = $this->entries($user, [...$filters, 'channel' => 'all'], $start, $end)
            ->selectRaw('channel, SUM(sales) as sales, SUM(cost) as cost')->groupBy('channel')->get();
        $sites = (clone $query)->whereNotNull('site_id')
            ->selectRaw('site_id, MAX(site_name) as name, SUM(sales) as sales, SUM(cost) as cost')->groupBy('site_id')->orderByDesc('sales')->get();
        $transactions = $this->transactions($user, $filters);
        $products = collect($this->products($user, $filters, $start, $end));
        $positiveProducts = $products->where('profit', '>', 0)->values();
        $losses = $products->where('profit', '<', 0)->values();
        $transactionChannels = (clone $query)->where('kind', 'sale')
            ->selectRaw('channel, SUM(sales) as sales')->groupBy('channel')->get();

        return [
            'granularity' => $granularity,
            'filters' => ['start_date' => $filters['start_date'] ?? null, 'end_date' => $filters['end_date'] ?? null, 'year' => $start->year, 'month' => $filters['month'] ?? null, 'channel' => $channel, 'site' => $filters['site'] ?? null, 'comparison' => $filters['comparison'] ?? 'previous'],
            'period' => ['start' => $start->toDateString(), 'end' => $end->format('Y-m-d H:i:s'), 'previous_start' => $previousStart->toDateString(), 'previous_end' => $previousEnd->format('Y-m-d H:i:s'), 'partial' => $end->isSameDay(now())],
            'summary' => $summary, 'previous' => $previous, 'series' => $series,
            'channels' => $mix->map(fn ($row): array => ['key' => $row->channel, 'name' => self::Channels[$row->channel], 'sales' => round((float) $row->sales, 2), 'cost' => round((float) $row->cost, 2)])->all(),
            'sites' => $sites->map(fn ($row): array => ['id' => (int) $row->site_id, 'name' => $row->name, 'sales' => round((float) $row->sales, 2), 'cost' => round((float) $row->cost, 2)])->all(),
            'products' => $positiveProducts->take(5)->all(), 'transactions' => $transactions,
            'product_distribution' => [
                'positive_total' => round($positiveProducts->sum('profit'), 2),
                'other_profit' => round($positiveProducts->skip(5)->sum('profit'), 2),
                'loss_total' => round($losses->sum('profit'), 2),
                'losses' => $losses->all(),
            ],
            'transaction_channels' => $transactionChannels->map(fn ($row): array => ['key' => $row->channel, 'name' => self::Channels[$row->channel], 'sales' => round((float) $row->sales, 2)])->all(),
            'notes' => [
                'Jualan POS selepas diskaun mengikut tarikh jualan; jualan pesanan yang telah dibayar dan tidak dibatalkan mengikut tarikh pesanan. Caj penghantaran tidak termasuk.',
                'Pulangan pelanggan ditolak daripada jualan produk. Modal item dikekalkan kerana rekod pulangan stok terperinci belum tersedia.',
                'Kos pesanan menggunakan kos produk / saiz clicker semasa. Kos POS mengutamakan snapshot asal; anggaran digunakan jika snapshot tiada. Kos yang belum diketahui tidak dinilai sebagai sifar sebenar.',
                'Komisen pelanggan dikira selepas pulangan. Diskaun pesanan ejen sudah termasuk dalam jualan bersih; bonus weekly closing lengkap dikira sekali pada tarikh akhir tempohnya.',
                'Gaji ialah bayaran yang telah direkodkan, mengikut tarikh kerja. Kos operasi lain belum direkodkan dalam modul ini. Untung ialah anggaran, bukan untung bersih muktamad.',
                'Penapis lokasi hanya mengandungi POS dan gaji lokasi tersebut. Pesanan dalam talian serta bonus tanpa lokasi tidak diagihkan secara rekaan.',
                'Paparan mengikut akses modul anda. Gaji / bonus tidak termasuk jika akses Salary Management / Weekly Closing tiada.',
            ],
        ];
    }

    private function totals(Builder $query): array
    {
        $row = $query->selectRaw('COALESCE(SUM(sales),0) as sales, COALESCE(SUM(capital),0) as capital, COALESCE(SUM(commission),0) as commission, COALESCE(SUM(salary),0) as salary, COALESCE(SUM(bonus),0) as bonus, COALESCE(SUM(cost),0) as cost, COALESCE(SUM(units),0) as units, COALESCE(SUM(estimated),0) as estimated, COALESCE(SUM(missing),0) as missing, COALESCE(SUM(CASE WHEN kind = \'sale\' THEN 1 ELSE 0 END),0) as transactions')->first();
        $totals = [];
        foreach (['sales', 'capital', 'commission', 'salary', 'bonus', 'cost'] as $key) {
            $totals[$key] = round((float) $row->$key, 2);
        }
        foreach (['units', 'estimated', 'missing', 'transactions'] as $key) {
            $totals[$key] = (int) $row->$key;
        }
        $totals['profit'] = round($totals['sales'] - $totals['cost'], 2);
        $totals['margin'] = $totals['sales'] > 0 ? round($totals['profit'] / $totals['sales'] * 100, 1) : null;

        return $totals;
    }

    /** All branches are SELECT-only; no stock, payment or model mutation methods are called. */
    public function entries(AdminUser $user, array $filters, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        $allowed = $this->channels($user);
        $channel = $filters['channel'] ?? 'all';
        $site = $filters['site'] ?? null;
        abort_if($channel !== 'all' && ! isset($allowed[$channel]), 403);
        abort_if($site && ! isset($allowed['pos']), 403);
        $branches = [];
        if (isset($allowed['pos']) && in_array($channel, ['all', 'pos'], true)) {
            $unitCost = $this->posUnitCost();
            $items = DB::table('pos_sale_items as i')->leftJoin('products as p', 'p.id', '=', 'i.product_id');
            $this->joinPosCharacterPrice($items);
            $items->whereNull('i.deleted_at')
                ->selectRaw("i.pos_sale_id, SUM(COALESCE({$unitCost},0)*i.quantity) as capital, SUM(i.quantity) as units, SUM(CASE WHEN i.unit_cost IS NULL THEN i.quantity ELSE 0 END) as estimated, SUM(CASE WHEN ({$unitCost}) IS NULL THEN i.quantity ELSE 0 END) as missing")->groupBy('i.pos_sale_id');
            $branches[] = DB::table('pos_sales as s')->leftJoinSub($items, 'i', 'i.pos_sale_id', '=', 's.id')->leftJoin('business_sites as b', 'b.id', '=', 's.business_site_id')
                ->whereNull('s.voided_at')->whereBetween('s.sold_at', [$start, $end])->when($site, fn (Builder $q) => $q->where('s.business_site_id', $site))
                ->selectRaw("s.id, s.sale_number as reference, s.sold_at as date, 'pos' as channel, 'sale' as kind, s.business_site_id as site_id, b.site_name, s.total_amount as sales, COALESCE(i.capital,0) as capital, 0 as commission, 0 as salary, 0 as bonus, COALESCE(i.units,0) as units, COALESCE(i.estimated,0) as estimated, COALESCE(i.missing,1) as missing");
            if (AdminAccess::allows($user, 'salary-management.view')) {
                $uniqueSites = DB::table('business_sites')->selectRaw('site_name, MIN(id) as id')->groupBy('site_name')->havingRaw('COUNT(*) = 1');
                $branches[] = DB::table('salary_payments as s')->leftJoin('staff_salary_drafts as d', 'd.id', '=', 's.staff_salary_draft_id')->leftJoin('business_site_operations as o', 'o.id', '=', 'd.business_site_operation_id')->leftJoinSub($uniqueSites, 'b', 'b.site_name', '=', 's.site_name')
                    ->whereBetween('s.work_date', [$start->toDateString(), $end->toDateString()])->when($site, fn (Builder $q) => $q->whereRaw('COALESCE(o.business_site_id,b.id) = ?', [$site]))
                    ->selectRaw("s.id, CONCAT('SAL-',s.id) as reference, s.work_date as date, 'pos' as channel, 'salary' as kind, COALESCE(o.business_site_id,b.id) as site_id, s.site_name, 0 as sales, 0 as capital, 0 as commission, s.amount_cents/100 as salary, 0 as bonus, 0 as units, 0 as estimated, 0 as missing");
            }
        }
        foreach (['orders' => 'orders', 'customer' => 'customer_orders'] as $key => $table) {
            if (! isset($allowed[$key]) || ! in_array($channel, ['all', $key], true) || $site) {
                continue;
            }
            $itemTable = $key === 'orders' ? 'order_items' : 'customer_order_items';
            $unitCost = "CASE WHEN i.clicker_character_count IS NOT NULL OR p.product_type = 'clicker' THEN cp.cost_rm ELSE p.cost_rm END";
            $items = DB::table($itemTable.' as i')->leftJoin('products as p', 'p.id', '=', 'i.product_id')->leftJoin('product_clicker_prices as cp', function ($join): void {
                $join->on('cp.product_id', '=', 'i.product_id')->on('cp.character_count', '=', 'i.clicker_character_count');
            })->selectRaw("i.order_id, SUM(COALESCE({$unitCost},0)*i.quantity) as capital, SUM(i.quantity) as units, SUM(CASE WHEN ({$unitCost}) IS NULL THEN i.quantity ELSE 0 END) as missing")->groupBy('i.order_id');
            $sales = $key === 'customer' ? 'GREATEST(0,s.subtotal-s.refunded_product_amount)' : 's.subtotal';
            $commission = $key === 'customer' ? "ROUND(({$sales})*s.commission_rate/100,2)" : '0';
            $branches[] = DB::table($table.' as s')->leftJoinSub($items, 'i', 'i.order_id', '=', 's.id')
                ->where('s.payment_status', 'paid')->where('s.status', '!=', 'cancelled')->whereBetween('s.placed_at', [$start, $end])
                ->selectRaw("s.id, s.order_number as reference, s.placed_at as date, '{$key}' as channel, 'sale' as kind, NULL as site_id, NULL as site_name, {$sales} as sales, COALESCE(i.capital,0) as capital, {$commission} as commission, 0 as salary, 0 as bonus, COALESCE(i.units,0) as units, COALESCE(i.units,0) as estimated, COALESCE(i.missing,1) as missing");
        }
        if (! $site && isset($allowed['orders']) && in_array($channel, ['all', 'orders'], true) && AdminAccess::allows($user, 'weekly-closings.view')) {
            $branches[] = DB::table('weekly_closings as s')->where('s.status', 'completed')->whereBetween('s.period_end', [$start, $end])
                ->selectRaw("s.id, s.week_key as reference, s.period_end as date, 'orders' as channel, 'bonus' as kind, NULL as site_id, NULL as site_name, 0 as sales, 0 as capital, 0 as commission, 0 as salary, s.total_payable_bonus as bonus, 0 as units, 0 as estimated, 0 as missing");
        }
        $union = array_shift($branches) ?? DB::query()->selectRaw("0 as id, '' as reference, NULL as date, 'pos' as channel, 'sale' as kind, NULL as site_id, NULL as site_name, 0 as sales, 0 as capital, 0 as commission, 0 as salary, 0 as bonus, 0 as units, 0 as estimated, 0 as missing")->whereRaw('1=0');
        foreach ($branches as $branch) {
            $union->unionAll($branch);
        }

        return DB::query()->fromSub(DB::query()->fromSub($union, 'ledger')->selectRaw('ledger.*, capital+commission+salary+bonus as cost'), 'entries');
    }

    public function transactions(AdminUser $user, array $filters): array
    {
        [$start, $end] = $this->ranges($filters);
        $query = $this->entries($user, $filters, $start, $end)
            ->when(isset($filters['day']), fn (Builder $q) => $q->whereDate('date', $start->day((int) $filters['day'])->toDateString()));
        if (isset($filters['cost_quality'])) {
            $query->where('kind', 'sale');
            match ($filters['cost_quality']) {
                'estimated' => $query->where('estimated', '>', 0),
                'missing' => $query->where('missing', '>', 0),
                default => $query->where(fn (Builder $q) => $q->where('estimated', '>', 0)->orWhere('missing', '>', 0)),
            };
        }
        $page = (int) ($filters['page'] ?? 1);
        $total = (clone $query)->count();
        $rows = $query->orderByDesc('date')->orderBy('channel')->orderBy('kind')->orderByDesc('id')->forPage($page, 20)->get();

        $items = isset($filters['cost_quality']) ? $this->costItems($rows) : [];

        return ['page' => $page, 'total' => $total, 'per_page' => 20, 'rows' => $rows->map(fn ($row): array => [
            ...$this->transaction($row),
            'estimated_units' => (int) $row->estimated,
            'missing_units' => (int) $row->missing,
            'capital' => round((float) $row->capital, 2),
            'cost_items' => $items[$row->channel.':'.$row->id] ?? [],
        ])->all()];
    }

    /** @return array<string, list<array{name: string, quantity: int, unit_cost: ?float, source: string}>> */
    private function posUnitCost(): string
    {
        return "COALESCE(i.unit_cost,CASE WHEN i.clicker_configuration IS NOT NULL OR p.product_type = 'clicker' THEN cp.cost_rm ELSE p.cost_rm END)";
    }

    private function joinPosCharacterPrice(Builder $query): void
    {
        $query->leftJoin('product_clicker_prices as cp', function ($join): void {
            $join->on('cp.product_id', '=', 'i.product_id')
                ->whereRaw("cp.character_count = CAST(JSON_UNQUOTE(JSON_EXTRACT(i.clicker_configuration, '$.character_count')) AS UNSIGNED)");
        });
    }

    private function costItems(Collection $rows): array
    {
        $result = [];
        foreach (['pos' => ['pos_sale_items', 'pos_sale_id'], 'orders' => ['order_items', 'order_id'], 'customer' => ['customer_order_items', 'order_id']] as $channel => [$table, $foreign]) {
            $ids = $rows->where('channel', $channel)->pluck('id');
            if ($ids->isEmpty()) {
                continue;
            }
            $query = DB::table($table.' as i')->leftJoin('products as p', 'p.id', '=', 'i.product_id')->whereIn('i.'.$foreign, $ids);
            if ($channel === 'pos') {
                $query->whereNull('i.deleted_at');
                $this->joinPosCharacterPrice($query);
                $cost = $this->posUnitCost();
                $characterCount = "CAST(JSON_UNQUOTE(JSON_EXTRACT(i.clicker_configuration, '$.character_count')) AS UNSIGNED)";
                $snapshot = 'CASE WHEN i.unit_cost IS NULL THEN 0 ELSE 1 END';
                $variant = "CASE WHEN ({$characterCount}) BETWEEN 1 AND 8 THEN CONCAT(' · ',({$characterCount}),' huruf') ELSE '' END";
            } else {
                $query->leftJoin('product_clicker_prices as cp', function ($join): void {
                    $join->on('cp.product_id', '=', 'i.product_id')->on('cp.character_count', '=', 'i.clicker_character_count');
                });
                $cost = "CASE WHEN i.clicker_character_count IS NOT NULL OR p.product_type = 'clicker' THEN cp.cost_rm ELSE p.cost_rm END";
                $snapshot = '0';
                $characterCount = 'i.clicker_character_count';
                $variant = "CASE WHEN i.clicker_character_count IS NOT NULL THEN CONCAT(' · ',i.clicker_character_count,' huruf') ELSE '' END";
            }
            foreach ($query->selectRaw("i.{$foreign} as record_id, i.product_name, i.quantity, {$cost} as unit_cost, {$snapshot} as snapshot, {$variant} as variant, p.product_type, {$characterCount} as character_count")->orderBy('i.id')->get() as $item) {
                $result[$channel.':'.$item->record_id][] = [
                    'name' => $item->product_name.$item->variant,
                    'quantity' => (int) $item->quantity,
                    'total_cost' => $item->unit_cost === null ? null : round((float) $item->unit_cost * (int) $item->quantity, 2),
                    'unit_cost' => $item->unit_cost === null ? null : (float) $item->unit_cost,
                    'source' => $item->unit_cost === null ? ($item->product_type === 'clicker' && ! in_array((int) $item->character_count, range(1, 8), true) ? 'variant_missing' : 'missing') : ((int) $item->snapshot === 1 ? 'snapshot' : 'current'),
                ];
            }
        }

        return $result;
    }

    public function transaction(object $row): array
    {
        $route = match ($row->kind) {
            'salary' => 'admin.salary-management.show', 'bonus' => 'admin.weekly-closings.show',
            default => match ($row->channel) {
                'pos' => 'admin.sales.show', 'orders' => 'admin.orders.show', default => 'admin.customer-orders.show'
            },
        };

        return ['reference' => $row->reference, 'date' => $row->date, 'channel' => self::Channels[$row->channel], 'kind' => $row->kind, 'site' => $row->site_name, 'sales' => round((float) $row->sales, 2), 'cost' => round((float) $row->cost, 2), 'profit' => round((float) $row->sales - (float) $row->cost, 2), 'url' => route($route, $row->id)];
    }

    private function products(AdminUser $user, array $filters, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $sales = $this->entries($user, $filters, $start, $end)->where('kind', 'sale');
        $branches = [];
        foreach (['pos' => ['pos_sale_items', 'pos_sale_id'], 'orders' => ['order_items', 'order_id'], 'customer' => ['customer_order_items', 'order_id']] as $key => [$table, $foreign]) {
            $q = DB::table($table.' as i')->joinSub((clone $sales)->where('channel', $key), 's', 's.id', '=', 'i.'.$foreign)->leftJoin('products as p', 'p.id', '=', 'i.product_id');
            if ($key === 'pos') {
                $q->whereNull('i.deleted_at');
                $this->joinPosCharacterPrice($q);
                $cost = 'COALESCE('.$this->posUnitCost().',0)*i.quantity';
                $amount = 'i.line_total';
            } else {
                $q->leftJoin('product_clicker_prices as cp', function ($join): void {
                    $join->on('cp.product_id', '=', 'i.product_id')->on('cp.character_count', '=', 'i.clicker_character_count');
                });
                $cost = "(CASE WHEN i.clicker_character_count IS NOT NULL OR p.product_type = 'clicker' THEN COALESCE(cp.cost_rm,0) ELSE COALESCE(p.cost_rm,0) END)*i.quantity";
                $sourceTable = $key === 'orders' ? 'orders' : 'customer_orders';
                $q->join($sourceTable.' as original', 'original.id', '=', 's.id');
                $amount = 'CASE WHEN original.subtotal > 0 THEN i.line_total*s.sales/original.subtotal ELSE 0 END';
            }
            $commission = 'CASE WHEN s.sales > 0 THEN ('.$amount.')*s.commission/s.sales ELSE 0 END';
            $branches[] = $q->selectRaw("i.product_id, i.product_name as name, i.quantity as units, {$amount} as sales, ({$amount})-({$cost})-({$commission}) as profit");
        }
        $union = array_shift($branches);
        foreach ($branches as $branch) {
            $union->unionAll($branch);
        }

        return DB::query()->fromSub($union, 'items')->selectRaw('product_id, MAX(name) as name, SUM(units) as units, SUM(sales) as sales, SUM(profit) as profit')->groupBy('product_id')->orderByDesc('profit')->orderBy('product_id')->get()
            ->map(fn ($row): array => ['name' => $row->name, 'units' => (int) $row->units, 'sales' => round((float) $row->sales, 2), 'profit' => round((float) $row->profit, 2)])->all();
    }
}
