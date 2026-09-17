<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSession;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BusinessSiteOperationController extends Controller
{
    public function show(BusinessSiteOperation $businessSiteOperation): View
    {
        $businessSiteOperation->load('businessSite:id,site_name,city');
        $periodEnd = $businessSiteOperation->closed_at ?? now();
        $salesQuery = fn (): Builder => $this->salesQuery($businessSiteOperation);

        $salesTotal = $salesQuery()
            ->toBase()
            ->selectRaw('COUNT(*) as sales_count, COALESCE(SUM(total_amount), 0) as sales_total')
            ->first();

        $itemsTable = (new PosSaleItem)->getTable();
        $productsTable = (new Product)->getTable();
        $itemTotals = PosSaleItem::query()
            ->join($productsTable, "{$productsTable}.id", '=', "{$itemsTable}.product_id")
            ->whereHas('posSale', fn (Builder $query): Builder => $query
                ->notVoided()->whereBelongsTo($businessSiteOperation, 'businessSiteOperation'))
            ->toBase()
            ->selectRaw("COALESCE(SUM({$itemsTable}.quantity), 0) as items_sold")
            ->selectRaw("COALESCE(SUM(COALESCE({$itemsTable}.unit_cost, {$productsTable}.cost_rm, 0) * {$itemsTable}.quantity), 0) as capital_total")
            ->first();

        $netCompanyTotal = (float) $salesTotal->sales_total;
        $capitalTotal = (float) $itemTotals->capital_total;

        return view('admin.business-site-operations.show', [
            'operation' => $businessSiteOperation,
            'closureCorrections' => Schema::hasTable('operation_closure_corrections')
                ? DB::table('operation_closure_corrections as c')->join('usr_admin as a', 'a.id', '=', 'c.admin_id')->where('c.business_site_operation_id', $businessSiteOperation->id)->select('c.*', 'a.name as admin_name')->latest('c.id')->get() : collect(),
            'summary' => [
                'sales_count' => (int) $salesTotal->sales_count,
                'sales_total' => (float) $salesTotal->sales_total,
                'items_sold' => (int) $itemTotals->items_sold,
                'net_company_total' => $netCompanyTotal,
                'capital_total' => $capitalTotal,
                'gross_profit_total' => $netCompanyTotal - $capitalTotal,
            ],
            'attendances' => PosSession::query()
                ->where('business_site_id', $businessSiteOperation->business_site_id)
                ->where('signed_in_at', '<=', $periodEnd)
                ->where(fn (Builder $query): Builder => $query->whereNull('signed_out_at')->orWhere('signed_out_at', '>=', $businessSiteOperation->opened_at))
                ->with('agent:id,agt_name,login_id')
                ->oldest('signed_in_at')
                ->paginate(20, ['*'], 'attendance_page')
                ->withQueryString(),
            'sales' => PosSale::query()->whereBelongsTo($businessSiteOperation, 'businessSiteOperation')
                ->with(['salesAgent:id,agt_name,login_id', 'recordedBy:id,agt_name,login_id'])
                ->withCount('items')
                ->withSum('items as items_sold', 'quantity')
                ->latest('sold_at')
                ->paginate(20, ['*'], 'sales_page')
                ->withQueryString(),
        ]);
    }

    public function destroy(Request $request, BusinessSiteOperation $businessSiteOperation): RedirectResponse
    {
        return back()->withErrors([
            'business_site_operation' => 'Business sessions are retained for history and cannot be deleted.',
        ]);
    }

    private function salesQuery(BusinessSiteOperation $businessSiteOperation): Builder
    {
        return PosSale::query()->notVoided()->whereBelongsTo($businessSiteOperation, 'businessSiteOperation');
    }
}
