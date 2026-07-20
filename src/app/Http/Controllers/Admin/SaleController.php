<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSite;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'business_site_id' => $request->integer('business_site_id'),
            'payment_method' => $request->string('payment_method')->trim()->toString(),
            'period' => array_key_exists($request->string('period')->toString(), $this->periodOptions())
                ? $request->string('period')->toString()
                : 'today',
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

        return view('admin.sales.index', [
            'sales' => $sales,
            'filters' => $filters,
            'businessSites' => BusinessSite::query()->orderBy('site_name')->get(['id', 'site_name', 'city']),
            'paymentMethods' => PosSale::paymentMethods(),
            'periodOptions' => $this->periodOptions(),
            'periodLabel' => $this->periodOptions()[$filters['period']],
            'summary' => [
                'transaction_count' => (int) $totals->transaction_count,
                'total_amount' => (float) $totals->total_amount,
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
            'recordedBy:id,agt_name,login_id,email,phone_number',
            'posSession:id,agent_id,business_site_id,signed_in_at,expires_at,signed_out_at',
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
                    fn (PosSaleItem $item): float => (float) $item->discount_amount,
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
        [$periodStart, $periodEnd] = $this->periodRange($filters['period']);

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
