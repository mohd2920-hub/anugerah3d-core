<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ProductStockOverview
{
    public function rows(bool $includeHidden = false): Builder
    {
        $products = DB::table('products as p')
            ->where(fn (Builder $query) => $query->where('p.casing_stock_enabled', false)->orWhereNull('p.casing_stock_enabled'))
            ->selectRaw('p.id as product_id, p.prd_name, p.prd_code, p.prd_picture as image_path, p.is_visible_to_agents, NULL as casing_id, NULL as casing_name, NULL as character_count, p.prd_balance as balance');

        $casings = DB::table('products as p')
            ->join('product_clicker_images as c', function ($join): void {
                $join->on('c.product_id', '=', 'p.id')->where('c.image_type', 'casing');
            })
            ->join('product_clicker_prices as price', 'price.product_id', '=', 'p.id')
            ->leftJoin('product_clicker_stocks as stock', function ($join): void {
                $join->on('stock.casing_image_id', '=', 'c.id')->on('stock.character_count', '=', 'price.character_count');
            })
            ->where('p.casing_stock_enabled', true)
            ->whereBetween('price.character_count', [1, 8])
            ->whereExists(function (Builder $query): void {
                $query->selectRaw('1')->from('product_clicker_results as result')
                    ->whereColumn('result.product_id', 'p.id')->whereColumn('result.casing_image_id', 'c.id');
            })
            ->selectRaw('p.id as product_id, p.prd_name, p.prd_code, c.image_path, p.is_visible_to_agents, c.id as casing_id, c.alt_text as casing_name, price.character_count, COALESCE(stock.quantity, 0) as balance');

        if ((new Product)->discontinuationAvailable()) {
            $products->whereNull('p.discontinued_at');
            $casings->whereNull('p.discontinued_at');
        }

        return DB::query()->fromSub($products->unionAll($casings), 'inventory')
            ->when(! $includeHidden, fn (Builder $query) => $query->where('is_visible_to_agents', true));
    }

    public function statistics(bool $includeHidden = false, string $search = ''): array
    {
        $matchingProducts = Product::query()->when($search !== '', fn (\Illuminate\Database\Eloquent\Builder $query) => $query->search($search))->select('id');
        $stock = $this->rows($includeHidden)->whereIn('product_id', $matchingProducts)->selectRaw('SUM(CASE WHEN balance <= 0 THEN 1 ELSE 0 END) as empty, SUM(CASE WHEN balance BETWEEN 1 AND 4 THEN 1 ELSE 0 END) as critical, SUM(CASE WHEN balance >= 5 THEN 1 ELSE 0 END) as healthy')->first();
        $productsQuery = DB::table('products')->whereIn('id', $matchingProducts)->selectRaw('COUNT(*) as total');
        if ((new Product)->discontinuationAvailable()) {
            $productsQuery->selectRaw('SUM(CASE WHEN is_visible_to_agents = 0 AND discontinued_at IS NULL THEN 1 ELSE 0 END) as hidden, SUM(CASE WHEN discontinued_at IS NOT NULL THEN 1 ELSE 0 END) as discontinued, SUM(CASE WHEN discontinued_at IS NOT NULL AND prd_balance > 0 THEN 1 ELSE 0 END) as discontinued_stock');
        } else {
            $productsQuery->selectRaw('SUM(CASE WHEN is_visible_to_agents = 0 THEN 1 ELSE 0 END) as hidden, 0 as discontinued, 0 as discontinued_stock');
        }
        $products = $productsQuery->first();

        return ['empty' => (int) $stock->empty, 'critical' => (int) $stock->critical, 'healthy' => (int) $stock->healthy, 'all' => (int) $products->total, 'hidden' => (int) $products->hidden, 'discontinued' => (int) $products->discontinued, 'discontinued_stock' => (int) $products->discontinued_stock, 'catalogue' => Product::query()->visibleToAgents()->whereIn('id', $matchingProducts)->count()];
    }

    public function filtered(string $filter, string $search, bool $includeHidden, bool $productSearchOnly = false): Builder
    {
        return $this->rows($includeHidden)
            ->when($filter === 'empty', fn (Builder $query) => $query->where('balance', '<=', 0))
            ->when($filter === 'critical', fn (Builder $query) => $query->whereBetween('balance', [1, 4]))
            ->when($filter === 'healthy', fn (Builder $query) => $query->where('balance', '>=', 5))
            ->when($filter === 'low', fn (Builder $query) => $query->where('balance', '<', 5))
            ->when($search !== '' && $productSearchOnly, fn (Builder $query) => $query->whereIn('product_id', Product::query()->search($search)->select('id')))
            ->when($search !== '' && ! $productSearchOnly, fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('prd_name', 'like', '%'.$search.'%')->orWhere('prd_code', 'like', '%'.$search.'%')->orWhere('casing_name', 'like', '%'.$search.'%')))
            ->orderBy('balance')->orderBy('product_id')->orderBy('casing_id')->orderBy('character_count');
    }
}
