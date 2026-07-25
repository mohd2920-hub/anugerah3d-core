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

class OrderController extends Controller
{
    public function create(Request $request): View
    {
        /** @var Agent $agent */
        $agent = $request->user('agent');

        return view('agent.orders.create', [
            'agent' => $agent,
            'products' => Product::query()
                ->with([
                    'materialType',
                    'images:id,product_id,image_path,alt_text,position',
                ])
                ->orderByDesc('prd_balance')
                ->orderBy('prd_name')
                ->get(),
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
