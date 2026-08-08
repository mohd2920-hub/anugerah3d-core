<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSession;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $itemsSold = PosSaleItem::query()
            ->whereHas('posSale', fn (Builder $query): Builder => $query
                ->whereBelongsTo($businessSiteOperation, 'businessSiteOperation'))
            ->sum('quantity');

        return view('admin.business-site-operations.show', [
            'operation' => $businessSiteOperation,
            'summary' => [
                'sales_count' => (int) $salesTotal->sales_count,
                'sales_total' => (float) $salesTotal->sales_total,
                'items_sold' => (int) $itemsSold,
            ],
            'attendances' => PosSession::query()
                ->where('business_site_id', $businessSiteOperation->business_site_id)
                ->whereBetween('signed_in_at', [$businessSiteOperation->opened_at, $periodEnd])
                ->with('agent:id,agt_name,login_id')
                ->oldest('signed_in_at')
                ->paginate(20, ['*'], 'attendance_page')
                ->withQueryString(),
            'sales' => $salesQuery()
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
        if (! $businessSiteOperation->closed_at) {
            return back()->withErrors(['business_site_operation' => 'Close this business session before deleting it.']);
        }

        if ($businessSiteOperation->sales()->exists()) {
            return back()->withErrors(['business_site_operation' => 'This business session has sales and cannot be deleted.']);
        }

        $operationId = $businessSiteOperation->getKey();
        $siteName = $businessSiteOperation->businessSite()->value('site_name');
        $businessSiteOperation->delete();

        AdminActivity::record(
            request: $request,
            event: 'admin.business_site_operation.deleted',
            description: "Business session {$operationId} for {$siteName} deleted.",
            adminUser: $request->user('admin'),
            properties: ['page' => 'Business Site Details', 'business_site_operation_id' => $operationId],
        );

        return redirect()->route('admin.business-sites.index')->with('success', 'Business session deleted successfully.');
    }

    private function salesQuery(BusinessSiteOperation $businessSiteOperation): Builder
    {
        return PosSale::query()->whereBelongsTo($businessSiteOperation, 'businessSiteOperation');
    }
}
