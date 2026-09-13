<?php

namespace App\Support;

use App\Models\AdminUser;
use App\Models\Product;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DashboardInventory
{
    public function data(array $filters, ?AdminUser $user = null): array
    {
        $query = $this->filteredRows($filters);
        if (isset($filters['stock_quality'])) {
            $query->where(function (Builder $quality) use ($filters): void {
                if ($filters['stock_quality'] === 'negative') {
                    $quality->where('available', '<', 0);
                } elseif ($filters['stock_quality'] === 'allocation') {
                    $quality->where('allocation_missing', 1);
                } else {
                    $quality->where('pricing_missing', 1);
                    if ($filters['stock_quality'] === 'all') {
                        $quality->orWhere('available', '<', 0)->orWhere('allocation_missing', 1);
                    }
                }
            });
        }
        $summary = (clone $query)->selectRaw('COUNT(*) as records, COALESCE(SUM(pricing_missing),0) as pricing_missing, COALESCE(SUM(allocation_missing),0) as allocation_missing, COALESCE(SUM(GREATEST(available,0)),0) as units, COALESCE(SUM(reserved),0) as reserved, COALESCE(SUM(GREATEST(available,0)*cost),0) as asset, COALESCE(SUM(reserved*cost),0) as reserved_asset, COALESCE(SUM(GREATEST(available,0)*price),0) as potential_sales, COALESCE(SUM(GREATEST(available,0)*(price-cost)),0) as potential_profit, COALESCE(SUM(CASE WHEN cost IS NULL OR price IS NULL THEN 1 ELSE 0 END),0) as unknown, COALESCE(SUM(CASE WHEN is_discontinued = 0 AND available BETWEEN 1 AND 4 THEN 1 ELSE 0 END),0) as low, COALESCE(SUM(CASE WHEN is_discontinued = 0 AND available <= 0 THEN 1 ELSE 0 END),0) as empty, COALESCE(SUM(CASE WHEN available < 0 THEN 1 ELSE 0 END),0) as negative')->first();
        $totals = [];
        foreach (['records', 'units', 'reserved', 'pricing_missing', 'allocation_missing', 'unknown', 'low', 'empty', 'negative'] as $key) {
            $totals[$key] = (int) $summary->$key;
        }
        foreach (['asset', 'reserved_asset', 'potential_sales', 'potential_profit'] as $key) {
            $totals[$key] = round((float) $summary->$key, 2);
        }
        $page = (int) ($filters['stock_page'] ?? 1);
        $rows = (clone $query)->orderBy('available')->orderBy('product_id')->orderBy('casing_id')->orderBy('character_count')->forPage($page, 20)->get();
        $leaders = (clone $query)->selectRaw('product_id, MAX(name) as name, SUM(GREATEST(available,0)*cost) as asset')->groupBy('product_id')->orderByDesc('asset')->limit(3)->get();

        return ['summary' => $totals, 'page' => $page, 'per_page' => 20, 'rows' => $rows->map(function ($row) use ($user): array {
            $available = max(0, (int) $row->available);
            $cost = $row->cost === null ? null : (float) $row->cost;
            $price = $row->price === null ? null : (float) $row->price;

            return ['edit_url' => $user !== null && AdminAccess::allows($user, 'products.edit') ? route('admin.products.edit', $row->product_id).($row->casing_id > 0 ? '#clicker-casing-'.$row->casing_id : '') : null, 'is_discontinued' => (bool) $row->is_discontinued, 'allocation_missing' => (bool) $row->allocation_missing, 'pricing_missing' => (bool) $row->pricing_missing, 'name' => $row->name, 'code' => $row->code, 'variant' => $row->variant, 'type' => $row->product_type, 'available' => (int) $row->available, 'reserved' => (int) $row->reserved, 'cost' => $cost, 'price' => $price, 'asset' => $cost === null ? null : round($available * $cost, 2), 'potential_profit' => $cost === null || $price === null ? null : round($available * ($price - $cost), 2), 'url' => route('admin.products.index', ['search' => $row->code])];
        })->all(), 'leaders' => $leaders->map(fn ($row): array => ['name' => $row->name, 'asset' => round((float) $row->asset, 2)])->all()];
    }

    private function filteredRows(array $filters): Builder
    {
        $query = $this->rows()
            ->when(! ($filters['stock_include_discontinued'] ?? false), fn (Builder $q) => $q->where('is_discontinued', false))
            ->when(($filters['stock_type'] ?? 'all') !== 'all', fn (Builder $q) => $q->where('product_type', $filters['stock_type']))
            ->when(trim($filters['stock_search'] ?? '') !== '', function (Builder $q) use ($filters): void {
                $search = '%'.trim($filters['stock_search']).'%';
                $q->where(fn (Builder $q) => $q->where('name', 'like', $search)->orWhere('code', 'like', $search)->orWhere('variant', 'like', $search));
            })
            ->when(($filters['stock_status'] ?? 'all') === 'low', fn (Builder $q) => $q->where('is_discontinued', false)->whereBetween('available', [1, 4]))
            ->when(($filters['stock_status'] ?? 'all') === 'out', fn (Builder $q) => $q->where('is_discontinued', false)->where('available', '<=', 0))
            ->when(($filters['stock_status'] ?? 'all') === 'healthy', fn (Builder $q) => $q->where('available', '>=', 5));

        return $query;
    }

    public function reservations(AdminUser $user, array $filters): array
    {
        $branches = [];
        $restricted = false;
        foreach (['orders' => 'order_items', 'customer_orders' => 'customer_order_items'] as $table => $items) {
            $module = $table === 'orders' ? 'orders' : 'customer-orders';
            if (! AdminAccess::allows($user, $module.'.view')) {
                $restricted = true;

                continue;
            }
            $branches[] = DB::table($items.' as i')->join($table.' as o', 'o.id', '=', 'i.order_id')
                ->joinSub($this->filteredRows($filters), 'stock', function ($join): void {
                    $join->on('stock.product_id', '=', 'i.product_id')
                        ->whereRaw('stock.casing_id = COALESCE(i.clicker_casing_image_id,0)')
                        ->whereRaw('stock.character_count = CASE WHEN i.clicker_casing_image_id IS NULL THEN 0 ELSE i.clicker_character_count END');
                })
                ->whereIn('o.status', ['pending', 'processing', 'ready', 'pickup_ready'])
                ->where('o.payment_status', '!=', 'refunded')->where('i.reserved_quantity', '>', 0)
                ->selectRaw("i.id as item_id, o.id as order_id, o.order_number, o.status, o.placed_at, '{$module}' as module, stock.name, stock.code, stock.variant, i.clicker_character_count as character_count, i.reserved_quantity as quantity, stock.cost");
        }
        abort_if($branches === [], 403);
        $union = array_shift($branches);
        foreach ($branches as $branch) {
            $union->unionAll($branch);
        }
        $query = DB::query()->fromSub($union, 'reservations');
        $summary = (clone $query)->selectRaw('COUNT(*) as records, COALESCE(SUM(quantity),0) as units, COALESCE(SUM(quantity*cost),0) as asset, COALESCE(SUM(CASE WHEN cost IS NULL THEN quantity ELSE 0 END),0) as unknown_units')->first();
        $page = (int) ($filters['stock_page'] ?? 1);
        $rows = $query->orderByDesc('placed_at')->orderBy('module')->orderBy('item_id')->forPage($page, 20)->get();

        return [
            'page' => $page, 'per_page' => 20, 'total' => (int) $summary->records,
            'units' => (int) $summary->units, 'asset' => round((float) $summary->asset, 2),
            'unknown_units' => (int) $summary->unknown_units, 'restricted' => $restricted,
            'rows' => $rows->map(fn ($row): array => [
                'reference' => $row->order_number, 'status' => $row->status, 'name' => $row->name,
                'code' => $row->code, 'variant' => $row->variant,
                'character_count' => $row->character_count, 'quantity' => (int) $row->quantity,
                'cost' => $row->cost === null ? null : (float) $row->cost,
                'total_cost' => $row->cost === null ? null : round((float) $row->cost * $row->quantity, 2),
                'url' => route('admin.'.$row->module.'.show', $row->order_id),
            ])->all(),
        ];
    }

    private function rows(): Builder
    {
        $reserved = [];
        foreach (['orders' => 'order_items', 'customer_orders' => 'customer_order_items'] as $orders => $items) {
            $reserved[] = DB::table($items.' as i')->join($orders.' as o', 'o.id', '=', 'i.order_id')
                ->whereIn('o.status', ['pending', 'processing', 'ready', 'pickup_ready'])->where('o.payment_status', '!=', 'refunded')
                ->selectRaw('i.product_id, COALESCE(i.clicker_casing_image_id,0) as casing_id, CASE WHEN i.clicker_casing_image_id IS NULL THEN 0 ELSE i.clicker_character_count END as character_count, SUM(i.reserved_quantity) as reserved')
                ->groupByRaw('i.product_id, COALESCE(i.clicker_casing_image_id,0), CASE WHEN i.clicker_casing_image_id IS NULL THEN 0 ELSE i.clicker_character_count END');
        }
        $reservation = DB::query()->fromSub($reserved[0]->unionAll($reserved[1]), 'r')->selectRaw('product_id, casing_id, character_count, SUM(reserved) as reserved')->groupBy('product_id', 'casing_id', 'character_count');
        $products = DB::table('products as p')->where(fn (Builder $q) => $q->where('p.casing_stock_enabled', false)->orWhereNull('p.casing_stock_enabled'))
            ->selectRaw("p.id as product_id, p.prd_name as name, p.prd_code as code, p.product_type, 0 as casing_id, 0 as character_count, '' as variant, p.prd_balance as available, CASE WHEN p.product_type = 'clicker' THEN NULL ELSE p.cost_rm END as cost, CASE WHEN p.product_type = 'clicker' THEN NULL ELSE p.price_selling END as price");
        $casings = DB::table('product_clicker_stocks as s')->join('product_clicker_images as c', 'c.id', '=', 's.casing_image_id')->join('products as p', 'p.id', '=', 'c.product_id')
            ->leftJoin('product_clicker_prices as cp', function ($join): void {
                $join->on('cp.product_id', '=', 'p.id')->on('cp.character_count', '=', 's.character_count');
            })->where('p.casing_stock_enabled', true)->where('c.image_type', 'casing')
            ->selectRaw("p.id as product_id, p.prd_name as name, p.prd_code as code, p.product_type, c.id as casing_id, s.character_count, CONCAT(COALESCE(c.alt_text,'Casing'), ' · ', s.character_count, ' huruf') as variant, s.quantity as available, cp.cost_rm as cost, cp.price_rm as price");

        $discontinued = (new Product)->discontinuationAvailable() ? 'CASE WHEN p.discontinued_at IS NULL THEN 0 ELSE 1 END' : '0';
        $products->selectRaw($discontinued.' as is_discontinued');
        $casings->selectRaw($discontinued.' as is_discontinued');

        $pricing = DB::table('product_clicker_prices')->whereBetween('character_count', [1, 8])
            ->selectRaw('product_id, COUNT(DISTINCT CASE WHEN cost_rm IS NOT NULL AND price_rm IS NOT NULL THEN character_count END) as complete_sizes')
            ->groupBy('product_id');

        return DB::query()->fromSub(DB::query()->fromSub($products->unionAll($casings), 'stock')->leftJoinSub($reservation, 'r', function ($join): void {
            $join->on('r.product_id', '=', 'stock.product_id')->on('r.casing_id', '=', 'stock.casing_id')->on('r.character_count', '=', 'stock.character_count');
        })->leftJoinSub($pricing, 'pricing', 'pricing.product_id', '=', 'stock.product_id')
            ->selectRaw("stock.*, COALESCE(r.reserved,0) as reserved, CASE WHEN stock.product_type = 'clicker' AND stock.casing_id = 0 THEN 1 ELSE 0 END as allocation_missing, CASE WHEN stock.product_type = 'clicker' AND stock.casing_id = 0 THEN CASE WHEN COALESCE(pricing.complete_sizes,0) = 8 THEN 0 ELSE 1 END ELSE CASE WHEN stock.cost IS NULL OR stock.price IS NULL THEN 1 ELSE 0 END END as pricing_missing"), 'inventory');
    }
}
