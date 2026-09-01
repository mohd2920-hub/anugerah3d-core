<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexSalesRequest;
use App\Models\BusinessSite;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SaleController extends Controller
{
    public function index(IndexSalesRequest $request): View
    {
        $validated = $request->validated();
        $filters = [
            'search' => trim((string) ($validated['search'] ?? '')),
            'business_site_id' => (int) ($validated['business_site_id'] ?? 0),
            'payment_method' => trim((string) ($validated['payment_method'] ?? '')),
            'period' => (string) ($validated['period'] ?? 'today'),
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ];

        $sales = $this->filteredSalesQuery($filters)
            ->with([
                'businessSite:id,site_name,city',
                'salesAgent:id,agt_name,login_id',
                'recordedBy:id,agt_name,login_id',
            ])
            ->withCount('items')
            ->withSum('items as total_units', 'quantity')
            ->latest('sold_at')
            ->paginate(20)
            ->withQueryString();

        $totals = $this->filteredSalesQuery($filters)
            ->toBase()
            ->selectRaw('COUNT(*) as transaction_count, COALESCE(SUM(total_amount), 0) as total_amount')
            ->first();
        $itemTotals = $this->salesItemTotals($filters);
        $totalCost = (float) $itemTotals->total_cost;

        return view('admin.sales.index', [
            'sales' => $sales,
            'filters' => $filters,
            'businessSites' => BusinessSite::query()->orderBy('site_name')->get(['id', 'site_name', 'city']),
            'paymentMethods' => PosSale::paymentMethods(),
            'periodOptions' => $this->periodOptions(),
            'periodLabel' => $this->periodLabel($filters),
            'summary' => [
                'transaction_count' => (int) $totals->transaction_count,
                'total_amount' => (float) $totals->total_amount,
                'total_units' => (int) $itemTotals->total_units,
                'gross_amount' => (float) $itemTotals->gross_amount,
                'discount_amount' => (float) $itemTotals->discount_amount,
                'total_cost' => $totalCost,
                'profit_amount' => round((float) $totals->total_amount - $totalCost, 2),
                'by_site' => $this->salesByBusinessSite($filters),
                'top_product' => $this->topProduct($filters),
                'top_agent' => $this->topAgent($filters),
            ],
        ]);
    }

    public function show(PosSale $sale): View
    {
        $sale->load([
            'businessSite:id,site_name,city',
            'salesAgent:id,agt_name,login_id,email,phone_number',
            'businessSiteOperation:id,business_site_id,opened_at,closed_at',
            'recordedBy:id,agt_name,login_id,email,phone_number',
            'posSession:id,agent_id,business_site_id,signed_in_at,signed_out_at',
            'items.product:id,prd_picture',
        ]);

        return view('admin.sales.show', [
            'sale' => $sale,
            'paymentMethods' => PosSale::paymentMethods(),
            'itemSummary' => [
                'gross_total' => $sale->items->sum(
                    fn (PosSaleItem $item): float => (float) $item->unit_price * $item->quantity,
                ),
                'discount_total' => $sale->items->sum(
                    fn (PosSaleItem $item): float => (float) $item->customer_discount_amount,
                ),
                'salesperson_commission_total' => $sale->items->sum(
                    fn (PosSaleItem $item): float => ((float) $item->unit_price * $item->quantity)
                        - (float) $item->agent_discount_amount
                        - (float) $item->customer_discount_amount,
                ),
            ],
        ]);
    }

    private function filteredSalesQuery(array $filters): Builder
    {
        return $this->applyFilters(PosSale::query(), $filters);
    }

    private function salesByBusinessSite(array $filters): Collection
    {
        return $this->filteredSalesQuery($filters)
            ->select('business_site_id')
            ->selectRaw('COUNT(*) as transaction_count, SUM(total_amount) as total_amount')
            ->with('businessSite:id,site_name,city')
            ->groupBy('business_site_id')
            ->orderByDesc('total_amount')
            ->get();
    }

    private function topProduct(array $filters): ?PosSaleItem
    {
        $matchingSales = $this->filteredSalesQuery($filters)
            ->select((new PosSale)->qualifyColumn('id'));

        return PosSaleItem::query()
            ->select('product_id')
            ->selectRaw('MAX(product_name) as product_name, MAX(product_code) as product_code')
            ->selectRaw('SUM(quantity) as total_quantity, SUM(line_total) as total_amount')
            ->whereIn('pos_sale_id', $matchingSales)
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->orderByDesc('total_amount')
            ->first();
    }

    private function topAgent(array $filters): ?PosSale
    {
        return $this->filteredSalesQuery($filters)
            ->select('sales_agent_id')
            ->selectRaw('COUNT(*) as transaction_count, SUM(total_amount) as total_amount')
            ->with('salesAgent:id,agt_name,login_id')
            ->groupBy('sales_agent_id')
            ->orderByDesc('total_amount')
            ->first();
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        [$periodStart, $periodEnd] = $this->dateRange($filters);

        return $query
            ->whereBetween('sold_at', [$periodStart, $periodEnd])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $search = $filters['search'];

                    $query->where('sale_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhereHas('salesAgent', fn (Builder $agentQuery): Builder => $agentQuery->where('agt_name', 'like', "%{$search}%"))
                        ->orWhereHas('businessSite', fn (Builder $siteQuery): Builder => $siteQuery->where('site_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['business_site_id'] > 0, fn (Builder $query): Builder => $query->where('business_site_id', $filters['business_site_id']))
            ->when(array_key_exists($filters['payment_method'], PosSale::paymentMethods()), fn (Builder $query): Builder => $query->where('payment_method', $filters['payment_method']));
    }

    private function salesItemTotals(array $filters): object
    {
        $itemsTable = (new PosSaleItem)->getTable();
        $productsTable = (new Product)->getTable();
        $matchingSales = $this->filteredSalesQuery($filters)
            ->select((new PosSale)->qualifyColumn('id'));

        return PosSaleItem::query()
            ->leftJoin($productsTable, "{$productsTable}.id", '=', "{$itemsTable}.product_id")
            ->whereIn("{$itemsTable}.pos_sale_id", $matchingSales)
            ->toBase()
            ->selectRaw("COALESCE(SUM({$itemsTable}.quantity), 0) as total_units")
            ->selectRaw("COALESCE(SUM({$itemsTable}.unit_price * {$itemsTable}.quantity), 0) as gross_amount")
            ->selectRaw("COALESCE(SUM({$itemsTable}.agent_discount_amount + {$itemsTable}.customer_discount_amount), 0) as discount_amount")
            ->selectRaw("COALESCE(SUM(COALESCE({$productsTable}.cost_rm, 0) * {$itemsTable}.quantity), 0) as total_cost")
            ->first();
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    private function dateRange(array $filters): array
    {
        if ($filters['start_date'] !== null && $filters['end_date'] !== null) {
            return [
                now()->createFromFormat('Y-m-d', $filters['start_date'])->startOfDay(),
                now()->createFromFormat('Y-m-d', $filters['end_date'])->endOfDay(),
            ];
        }

        return $this->periodRange($filters['period']);
    }

    private function periodLabel(array $filters): string
    {
        if ($filters['start_date'] !== null && $filters['end_date'] !== null) {
            $start = now()->createFromFormat('Y-m-d', $filters['start_date'])->format('d M Y');
            $end = now()->createFromFormat('Y-m-d', $filters['end_date'])->format('d M Y');

            return $start === $end ? $start : "{$start} - {$end}";
        }

        return $this->periodOptions()[$filters['period']];
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    private function periodRange(string $period): array
    {
        return match ($period) {
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            '30_days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    /** @return array<string, string> */
    private function periodOptions(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'week' => 'This week',
            'month' => 'This month',
            '30_days' => '30 days',
        ];
    }
}
