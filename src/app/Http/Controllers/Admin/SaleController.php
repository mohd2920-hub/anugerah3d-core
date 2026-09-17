<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexSalesRequest;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
            'start_date' => $validated['single_date'] ?? $validated['start_date'] ?? null,
            'end_date' => $validated['single_date'] ?? $validated['end_date'] ?? null,
        ];

        $sales = $this->applyFilters(PosSale::query(), $filters)
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

        $reportSales = $this->filteredSalesQuery($filters)
            ->select(['sold_at', 'total_amount', 'report_date'])
            ->selectSub(BusinessSiteOperation::query()->select('opened_at')
                ->whereColumn('business_site_operations.id', 'pos_sales.business_site_operation_id'), 'session_opened_at');
        $totals = DB::query()->fromSub($reportSales, 'report_sales')
            ->selectRaw('COUNT(*) as transaction_count, COUNT(DISTINCT DATE(COALESCE(report_date, session_opened_at, sold_at))) as sales_days, COALESCE(SUM(total_amount), 0) as total_amount')
            ->first();
        $itemTotals = $this->salesItemTotals($filters);
        $totalCost = (float) $itemTotals->total_cost;
        $discountDetails = (bool) ($validated['show_discounts'] ?? false)
            ? $this->discountDetails($filters)
            : null;

        return view($request->routeIs('admin.sales.transactions') ? 'admin.sales.transactions' : 'admin.sales.index', [
            'sales' => $sales,
            'filters' => $filters,
            'activeFilterCount' => collect($filters)->only(['search', 'business_site_id', 'payment_method'])->filter(fn ($value): bool => $value !== null && $value !== '' && $value !== 0)->count(),
            'summaryReturnQuery' => collect($filters)->only(['period', 'start_date', 'end_date'])->filter(fn ($value): bool => $value !== null && $value !== '')->all(),
            'filterQuery' => array_filter($filters, fn ($value): bool => $value !== null && $value !== '' && $value !== 0),
            'businessSites' => BusinessSite::query()->orderBy('site_name')->get(['id', 'site_name', 'city']),
            'paymentMethods' => PosSale::paymentMethods(),
            'periodOptions' => $this->periodOptions(),
            'periodLabel' => $this->periodLabel($filters),
            'summary' => [
                'transaction_count' => (int) $totals->transaction_count,
                'sales_days' => (int) $totals->sales_days,
                'total_amount' => (float) $totals->total_amount,
                'total_units' => (int) $itemTotals->total_units,
                'discounted_units' => (int) $itemTotals->discounted_units,
                'gross_amount' => (float) $itemTotals->gross_amount,
                'discount_amount' => (float) $itemTotals->discount_amount,
                'customer_discount_amount' => (float) $itemTotals->customer_discount_amount,
                'total_cost' => $totalCost,
                'missing_units' => (int) $itemTotals->missing_units,
                'profit_amount' => round((float) $totals->total_amount - $totalCost, 2),
                'by_site' => $this->salesByBusinessSite($filters),
                'top_products' => $this->topProducts($filters),
                'top_agents' => $this->topAgents($filters),
            ],
            'discountDetails' => $discountDetails,
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
            'items.product:id,product_type,prd_picture,cost_rm',
            'corrections.admin:id,name',
        ]);

        $prices = DB::table('product_clicker_prices')->whereIn('product_id', $sale->items->pluck('product_id'))
            ->get()->keyBy(fn ($price): string => $price->product_id.':'.$price->character_count);
        foreach ($sale->items as $item) {
            $count = $item->clicker_configuration['character_count'] ?? null;
            $clicker = $item->clicker_configuration !== null || $item->product?->product_type === 'clicker';
            $cost = $item->unit_cost ?? ($clicker ? $prices->get($item->product_id.':'.$count)?->cost_rm : $item->product?->cost_rm);
            $item->setAttribute('report_unit_cost', $cost === null ? null : (float) $cost);
            $item->setAttribute('report_cost_issue', $clicker && ! $count ? 'Maklumat varian belum lengkap' : 'Kos belum ditetapkan');
        }

        return view('admin.sales.show', [
            'sale' => $sale,
            'paymentMethods' => PosSale::paymentMethods(),
            'itemSummary' => $this->itemSummary($sale),
        ]);
    }

    /**
     * @return array{
     *     gross_total: float,
     *     discount_total: float,
     *     net_sales_total: float,
     *     net_company_total: float,
     *     capital_total: float,
     *     gross_profit_total: float
     * }
     */
    private function itemSummary(PosSale $sale): array
    {
        $grossTotal = (float) $sale->items->sum(
            fn (PosSaleItem $item): float => (float) $item->unit_price * $item->quantity,
        );
        $discountTotal = (float) $sale->items->sum(
            fn (PosSaleItem $item): float => (float) $item->customer_discount_amount,
        );
        $capitalTotal = (float) $sale->items->sum(
            fn (PosSaleItem $item): float => (float) ($item->report_unit_cost ?? 0) * $item->quantity,
        );
        $netSalesTotal = (float) $sale->total_amount;

        return [
            'gross_total' => $grossTotal,
            'discount_total' => $discountTotal,
            'net_sales_total' => $netSalesTotal,
            'net_company_total' => $netSalesTotal,
            'capital_total' => $capitalTotal,
            'cost_incomplete' => $sale->items->contains(fn ($item): bool => $item->report_unit_cost === null),
            'gross_profit_total' => $netSalesTotal - $capitalTotal,
        ];
    }

    private function filteredSalesQuery(array $filters): Builder
    {
        return $this->applyFilters(PosSale::query()->notVoided(), $filters);
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

    private function topProducts(array $filters): Collection
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
            ->orderBy('product_id')->limit(10)->get();
    }

    private function topAgents(array $filters): Collection
    {
        return $this->filteredSalesQuery($filters)
            ->select('sales_agent_id')
            ->selectRaw('COUNT(*) as transaction_count, SUM(total_amount) as total_amount')
            ->with('salesAgent:id,agt_name,login_id,profile_picture')
            ->groupBy('sales_agent_id')
            ->orderByDesc('total_amount')
            ->orderByDesc('transaction_count')->orderBy('sales_agent_id')->limit(3)->get();
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        [$periodStart, $periodEnd] = $this->dateRange($filters);

        return $query
            ->when($periodStart !== null, fn (Builder $query): Builder => $query->where(function (Builder $dates) use ($periodStart, $periodEnd): void {
                $dates->whereBetween('pos_sales.report_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->orWhere(fn (Builder $original): Builder => $original->whereNull('pos_sales.report_date')->where(function (Builder $sessionDates) use ($periodStart, $periodEnd): void {
                        $sessionDates->whereHas('businessSiteOperation', fn (Builder $operation): Builder => $operation->whereBetween('opened_at', [$periodStart, $periodEnd]))
                            ->orWhere(fn (Builder $legacy): Builder => $legacy->whereDoesntHave('businessSiteOperation')->whereBetween('sold_at', [$periodStart, $periodEnd]));
                    }));
            }))
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

        $cost = "COALESCE({$itemsTable}.unit_cost, CASE WHEN {$itemsTable}.clicker_configuration IS NOT NULL OR {$productsTable}.product_type = 'clicker' THEN cp.cost_rm ELSE {$productsTable}.cost_rm END)";

        return PosSaleItem::query()
            ->leftJoin($productsTable, "{$productsTable}.id", '=', "{$itemsTable}.product_id")
            ->leftJoin('product_clicker_prices as cp', function ($join) use ($itemsTable): void {
                $join->on('cp.product_id', '=', "{$itemsTable}.product_id")
                    ->whereRaw("cp.character_count = CAST(JSON_UNQUOTE(JSON_EXTRACT({$itemsTable}.clicker_configuration, '$.character_count')) AS UNSIGNED)");
            })
            ->whereIn("{$itemsTable}.pos_sale_id", $matchingSales)
            ->toBase()
            ->selectRaw("COALESCE(SUM({$itemsTable}.quantity), 0) as total_units")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$itemsTable}.customer_discount_amount > 0 THEN {$itemsTable}.quantity ELSE 0 END), 0) as discounted_units")
            ->selectRaw("COALESCE(SUM({$itemsTable}.unit_price * {$itemsTable}.quantity), 0) as gross_amount")
            ->selectRaw("COALESCE(SUM({$itemsTable}.customer_discount_amount), 0) as discount_amount")
            ->selectRaw("COALESCE(SUM({$itemsTable}.customer_discount_amount), 0) as customer_discount_amount")
            ->selectRaw("COALESCE(SUM(COALESCE({$cost}, 0) * {$itemsTable}.quantity), 0) as total_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN ({$cost}) IS NULL THEN {$itemsTable}.quantity ELSE 0 END),0) as missing_units")
            ->first();
    }

    private function discountDetails(array $filters): object
    {
        $matchingSales = $this->filteredSalesQuery($filters)
            ->select((new PosSale)->qualifyColumn('id'));

        return PosSaleItem::query()
            ->whereIn('pos_sale_id', $matchingSales)
            ->where('customer_discount_amount', '>', 0)
            ->with([
                'posSale:id,sale_number,sold_at,sales_agent_id,customer_name',
                'posSale.salesAgent:id,agt_name,login_id',
            ])
            ->latest('id')
            ->paginate(20, [
                'id',
                'pos_sale_id',
                'product_code',
                'product_name',
                'quantity',
                'customer_discount_amount',
            ], 'discount_page')
            ->withQueryString()
            ->fragment('discount-breakdown');
    }

    /** @return array{0: ?CarbonInterface, 1: ?CarbonInterface} */
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

    /** @return array{0: ?CarbonInterface, 1: ?CarbonInterface} */
    private function periodRange(string $period): array
    {
        return match ($period) {
            'all' => [null, null],
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
            'all' => 'Keseluruhan',
        ];
    }
}
