<?php

namespace App\Http\Controllers\Agent;

use App\Actions\Orders\PlaceAgentOrder;
use App\Actions\Orders\SendAgentOrderEmail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\StoreOrderRequest;
use App\Models\Agent;
use App\Models\Product;
use App\Support\CasingStock;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function create(Request $request): View
    {
        /** @var Agent $agent */
        $agent = $request->user('agent');

        return view('agent.orders.create', ['agent' => $agent, ...$this->catalogueData()]);
    }

    public function catalogueData(): array
    {
        $products = Product::query()
            ->visibleToAgents()
            ->with([
                'materialType',
                'images:id,product_id,image_path,alt_text,position',
            ])
            ->orderByDesc('prd_balance')
            ->orderBy('prd_name')
            ->get();

        $clickerCharacterPricesByProduct = DB::table('product_clicker_prices')
            ->whereIn('product_id', $products->modelKeys())
            ->orderBy('character_count')
            ->get()
            ->groupBy('product_id')
            ->map(fn ($rows): array => collect($rows)
                ->mapWithKeys(fn ($row): array => [
                    (int) $row->character_count => number_format((float) $row->price_rm, 2, '.', ''),
                ])
                ->all())
            ->all();

        $casingStocks = app(CasingStock::class)->quantities(DB::table('product_clicker_images')->whereIn('product_id', $products->modelKeys())->where('image_type', 'casing')->pluck('id')->all());
        $clickerImagesByProduct = DB::table('product_clicker_images')
            ->whereIn('product_id', $products->modelKeys())
            ->orderBy('image_type')
            ->orderBy('position')
            ->get()
            ->groupBy('product_id')
            ->map(function ($rows) use ($products, $casingStocks): array {
                $mapImages = fn (string $type): array => collect($rows)
                    ->where('image_type', $type)
                    ->values()
                    ->map(fn ($row): array => [
                        'id' => (int) $row->id,
                        'src' => filter_var($row->image_path, FILTER_VALIDATE_URL)
                            ? $row->image_path
                            : asset(ltrim((string) $row->image_path, '/')),
                        'alt' => $row->alt_text,
                        'stock' => $type === 'casing' && CasingStock::enabled($products->firstWhere('id', $row->product_id)) ? ($casingStocks[$row->id] ?? array_fill(1, 8, 0)) : null,
                    ])
                    ->all();

                return [
                    'casing' => $mapImages('casing'),
                    'huruf' => $mapImages('huruf'),
                ];
            })
            ->all();

        $clickerResultsByProduct = DB::table('product_clicker_results')
            ->whereIn('product_id', $products->modelKeys())
            ->orderBy('name')
            ->get(['id', 'product_id', 'casing_image_id', 'huruf_image_id', 'name', 'image_path'])
            ->groupBy('product_id')
            ->map(fn ($rows): array => collect($rows)->map(fn ($row): array => [
                'id' => (int) $row->id,
                'casingImageId' => (int) $row->casing_image_id,
                'hurufImageId' => (int) $row->huruf_image_id,
                'name' => (string) ($row->name ?? ''),
                'src' => filter_var($row->image_path, FILTER_VALIDATE_URL)
                    ? $row->image_path
                    : asset(ltrim((string) $row->image_path, '/')),
            ])->values()->all())
            ->all();

        return [
            'products' => $products,
            'clickerCharacterPricesByProduct' => $clickerCharacterPricesByProduct,
            'clickerImagesByProduct' => $clickerImagesByProduct,
            'clickerResultsByProduct' => $clickerResultsByProduct,
        ];
    }

    public function store(
        StoreOrderRequest $request,
        PlaceAgentOrder $placeAgentOrder,
        SendAgentOrderEmail $sendAgentOrderEmail,
    ): JsonResponse {
        /** @var Agent $agent */
        $agent = $request->user('agent');
        $order = $placeAgentOrder->handle($agent, $request->validated(), $request);

        if (
            $order->agent_submission_email_sent_at === null
            && $sendAgentOrderEmail->handle($order, 'submitted')
        ) {
            $order->forceFill(['agent_submission_email_sent_at' => now()])->save();
        }

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => [
                'number' => $order->order_number,
                'total' => $order->total_amount,
            ],
        ], 201);
    }
}
