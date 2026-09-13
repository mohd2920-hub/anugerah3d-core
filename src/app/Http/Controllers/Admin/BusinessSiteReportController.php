<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BusinessSiteReportRequest;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Support\BusinessSiteReport;
use Illuminate\Contracts\View\View;

class BusinessSiteReportController extends Controller
{
    public function directory(): View
    {
        return view('admin.business-sites.directory', [
            'businessSites' => BusinessSite::query()->withCount('operations')->orderBy('site_name')->paginate(24),
        ]);
    }

    public function statistics(BusinessSiteReportRequest $request, BusinessSiteReport $report): View
    {
        $filters = $request->validated();
        $summaries = $report->sites($filters);
        $combinedSummary = (object) collect(['sales_total', 'sales_count', 'items_sold', 'capital_total', 'operations_count'])->mapWithKeys(fn (string $key): array => [$key => $summaries->sum($key)])->all();

        $combinedSummary->operation_days = ($filters['mode'] ?? 'separate') === 'combined'
            ? $report->operationDays($filters)['total'] : 0;

        return view('admin.business-sites.statistics', [
            'filters' => $filters,
            'period' => $report->period($filters),
            'mode' => $filters['mode'] ?? 'separate',
            'businessSites' => BusinessSite::query()->orderBy('site_name')->get(['id', 'site_name']),
            'summaries' => $summaries,
            'combinedSummary' => $combinedSummary,
        ]);
    }

    public function summary(BusinessSiteReportRequest $request, BusinessSite $businessSite, BusinessSiteReport $report): View
    {
        $filters = $request->safe()->except(['site_ids', 'mode']);
        $operations = $report->filteredOperations($filters, $businessSite)->latest('opened_at')->orderByDesc('id')
            ->paginate(20, ['*'], 'operations_page')->withQueryString();
        $operations->getCollection()->each(function (BusinessSiteOperation $summary): void {
            $summary->setAttribute('net_company_total', (float) $summary->sales_total);
            $summary->setAttribute('gross_profit_total', (float) $summary->sales_total - (float) $summary->capital_total);
        });

        return view('admin.business-sites.summary', [
            'businessSite' => $businessSite,
            'filters' => $filters,
            'period' => $report->period($filters),
            'summary' => $report->sites($filters, $businessSite)->first(),
            'operationSummaries' => $operations,
        ]);
    }
}
