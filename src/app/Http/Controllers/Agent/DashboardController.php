<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var Agent $agent */
        $agent = $request->user('agent');
        $products = $this->catalogueQuery()->get();
        $catalogueProducts = $this->catalogueQuery()->paginate(12, ['*'], 'page', 1);

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
            'catalogueProducts' => $catalogueProducts,
            'topProducts' => $topProducts,
        ]);
    }

    public function cataloguePage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $page = (int) ($validated['page'] ?? 1);
        $search = trim((string) ($validated['search'] ?? ''));

        $products = $this->catalogueQuery()
            ->when($search !== '', function (Builder $query) use ($search): Builder {
                return $query->where(function (Builder $query) use ($search): void {
                    $query->where('prd_name', 'like', "%{$search}%")
                        ->orWhere('prd_code', 'like', "%{$search}%");
                });
            })
            ->paginate(12, ['*'], 'page', $page);

        $html = view('agent.partials._catalogue-cards', [
            'products' => collect($products->items()),
        ])->render();

        return response()->json([
            'html' => $html,
            'count' => $products->count(),
            'total' => $products->total(),
            'next_page' => $products->hasMorePages() ? $products->currentPage() + 1 : null,
            'has_more' => $products->hasMorePages(),
        ]);
    }

    private function catalogueQuery(): Builder
    {
        return Product::query()
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
            ->orderBy('prd_name');
    }
}
