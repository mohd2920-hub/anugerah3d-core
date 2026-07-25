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
        $products = Product::query()
            ->with([
                'materialType',
                'images:id,product_id,image_path,alt_text,position',
            ])
            ->withSum([
                'orderItems as ordered_units' => fn ($query) => $query->whereHas(
                    'order',
                    fn ($query) => $query->where('status', '!=', Order::StatusCancelled),
                ),
            ], 'quantity')
            ->withSum('posSaleItems as pos_units', 'quantity')
            ->orderByDesc('prd_balance')
            ->orderBy('prd_name')
            ->get();

        $topProducts = $products
            ->filter(fn (Product $product): bool => ((int) $product->ordered_units + (int) $product->pos_units) > 0)
            ->sortByDesc(fn (Product $product): int => (int) $product->ordered_units + (int) $product->pos_units)
            ->take(6)
            ->values();

        if ($topProducts->isEmpty()) {
            $topProducts = $products->take(6)->values();
        }

        return view('agent.dashboard', [
            'agent' => $agent,
            'products' => $products,
            'topProducts' => $topProducts,
        ]);
    }
}
