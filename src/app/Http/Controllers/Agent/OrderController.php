<?php

namespace App\Http\Controllers\Agent;

use App\Actions\Orders\PlaceAgentOrder;
use App\Actions\Orders\SendAgentOrderEmail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\StoreOrderRequest;
use App\Models\Agent;
use App\Models\Product;
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
        $products = Product::query()
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

        $clickerImagesByProduct = DB::table('product_clicker_images')
            ->whereIn('product_id', $products->modelKeys())
            ->orderBy('image_type')
            ->orderBy('position')
            ->get(['product_id', 'image_type', 'image_path', 'alt_text'])
            ->groupBy('product_id')
            ->map(function ($rows): array {
                $mapImages = fn (string $type): array => collect($rows)
                    ->where('image_type', $type)
                    ->values()
                    ->map(fn ($row): array => [
                        'src' => filter_var($row->image_path, FILTER_VALIDATE_URL)
                            ? $row->image_path
                            : asset(ltrim((string) $row->image_path, '/')),
                        'alt' => $row->alt_text,
                    ])
                    ->all();

                return [
                    'casing' => $mapImages('casing'),
                    'huruf' => $mapImages('huruf'),
                ];
            })
            ->all();

        return view('agent.orders.create', [
            'agent' => $agent,
            'products' => $products,
            'clickerCharacterPricesByProduct' => $clickerCharacterPricesByProduct,
            'clickerImagesByProduct' => $clickerImagesByProduct,
        ]);
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
