<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var Agent $agent */
        $agent = $request->user('agent');

        return view('agent.dashboard', [
            'agent' => $agent,
            'products' => Product::query()
                ->with([
                    'materialType',
                    'images:id,product_id,image_path,alt_text,position',
                ])
                ->orderByDesc('prd_balance')
                ->orderBy('prd_name')
                ->get(),
            'pendingOrderItemCount' => Order::query()
                ->whereBelongsTo($agent)
                ->whereIn('status', [Order::StatusPending, Order::StatusProcessing])
                ->sum('total_units'),
        ]);
    }
}
