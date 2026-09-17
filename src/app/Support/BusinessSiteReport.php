<?php

namespace App\Support;

use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSession;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessSiteReport
{
    public function operations(): Builder
    {
        $operationsTable = (new BusinessSiteOperation)->getTable();
        $sessionsTable = (new PosSession)->getTable();
        $salesTable = (new PosSale)->getTable();
        $itemsTable = (new PosSaleItem)->getTable();
        $productsTable = (new Product)->getTable();
        $sessionPeriodSql = "{$sessionsTable}.signed_in_at <= COALESCE({$operationsTable}.closed_at, CURRENT_TIMESTAMP) AND COALESCE({$sessionsTable}.signed_out_at, CURRENT_TIMESTAMP) >= {$operationsTable}.opened_at";

        $salesWithinOperation = fn (): Builder => PosSale::query()->notVoided()
            ->whereColumn("{$salesTable}.business_site_operation_id", "{$operationsTable}.id");

        $itemsWithinOperation = fn (): Builder => PosSaleItem::query()
            ->join($salesTable, "{$salesTable}.id", '=', "{$itemsTable}.pos_sale_id")
            ->whereNull("{$salesTable}.voided_at")
            ->whereColumn("{$salesTable}.business_site_operation_id", "{$operationsTable}.id");

        return BusinessSiteOperation::query()
            ->with('businessSite:id,site_name,city')
            ->select("{$operationsTable}.*")
            ->selectSub(
                PosSession::query()
                    ->selectRaw("COUNT(DISTINCT {$sessionsTable}.agent_id)")
                    ->whereColumn("{$sessionsTable}.business_site_id", "{$operationsTable}.business_site_id")
                    ->whereRaw($sessionPeriodSql),
                'agents_count',
            )
            ->selectSub($salesWithinOperation()->selectRaw('COUNT(*)'), 'sales_count')
            ->selectSub($salesWithinOperation()->selectRaw('COALESCE(SUM(total_amount), 0)'), 'sales_total')
            ->selectSub($itemsWithinOperation()->selectRaw("COALESCE(SUM({$itemsTable}.quantity), 0)"), 'items_sold')
            ->selectSub(
                $itemsWithinOperation()
                    ->join($productsTable, "{$productsTable}.id", '=', "{$itemsTable}.product_id")
                    ->selectRaw("COALESCE(SUM(COALESCE({$itemsTable}.unit_cost, {$productsTable}.cost_rm, 0) * {$itemsTable}.quantity), 0)"),
                'capital_total',
            );
    }

    /** @param array<string, mixed> $filters */
    public function filteredOperations(array $filters, ?BusinessSite $site = null): Builder
    {
        $query = $this->operations();
        if ($site !== null) {
            $query->where('business_site_operations.business_site_id', $site->getKey());
        } elseif (! empty($filters['site_ids'])) {
            $query->whereIn('business_site_operations.business_site_id', $filters['site_ids']);
        }
        [$start, $end] = $this->period($filters);
        if ($start !== null) {
            $query->where('opened_at', '>=', $start->setTimezone(config('app.timezone')))
                ->where('opened_at', '<', $end->setTimezone(config('app.timezone')));
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    public function period(array $filters): array
    {
        $today = CarbonImmutable::now('Asia/Kuala_Lumpur')->startOfDay();

        return match ($filters['period'] ?? 'month') {
            'all' => [null, null],
            'today' => [$today, $today->addDay()],
            'date' => [$day = CarbonImmutable::parse($filters['date'], 'Asia/Kuala_Lumpur'), $day->addDay()],
            'range' => [CarbonImmutable::parse($filters['from'], 'Asia/Kuala_Lumpur'), CarbonImmutable::parse($filters['to'], 'Asia/Kuala_Lumpur')->addDay()],
            'week' => [$today->startOfWeek(), $today->startOfWeek()->addWeek()],
            default => [$today->startOfMonth(), $today->startOfMonth()->addMonth()],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total: int, sites: array<int, int>}
     */
    public function operationDays(array $filters, ?BusinessSite $site = null): array
    {
        $days = [];
        $siteDays = [];
        $operations = $this->filteredOperations($filters, $site)
            ->select(['business_site_id', 'opened_at'])->distinct()->cursor();
        foreach ($operations as $operation) {
            $day = $operation->opened_at->timezone('Asia/Kuala_Lumpur')->toDateString();
            $days[$day] = true;
            $siteDays[$operation->business_site_id][$day] = true;
        }

        return ['total' => count($days), 'sites' => array_map(count(...), $siteDays)];
    }

    /** @param array<string, mixed> $filters */
    public function sites(array $filters, ?BusinessSite $site = null): Collection
    {
        $totals = DB::query()->fromSub($this->filteredOperations($filters, $site)->toBase(), 'sessions')
            ->selectRaw('business_site_id, COUNT(*) as operations_count, SUM(CASE WHEN closed_at IS NULL THEN 1 ELSE 0 END) as open_count, SUM(sales_count) as sales_count, SUM(sales_total) as sales_total, SUM(items_sold) as items_sold, SUM(capital_total) as capital_total')
            ->groupBy('business_site_id');

        $dayCounts = $this->operationDays($filters, $site)['sites'];

        return BusinessSite::query()->leftJoinSub($totals, 'totals', 'business_sites.id', '=', 'totals.business_site_id')
            ->select('business_sites.id', 'business_sites.site_name', 'business_sites.city')
            ->selectRaw('COALESCE(operations_count, 0) as operations_count, COALESCE(open_count, 0) as open_count, COALESCE(sales_count, 0) as sales_count, COALESCE(sales_total, 0) as sales_total, COALESCE(items_sold, 0) as items_sold, COALESCE(capital_total, 0) as capital_total')
            ->when($site !== null, fn (Builder $query): Builder => $query->where('business_sites.id', $site->getKey()))
            ->when($site === null && ! empty($filters['site_ids']), fn (Builder $query): Builder => $query->whereIn('business_sites.id', $filters['site_ids']))
            ->orderBy('site_name')->get()
            ->each(fn (BusinessSite $summary) => $summary->setAttribute('operation_days', $dayCounts[$summary->id] ?? 0));
    }
}
