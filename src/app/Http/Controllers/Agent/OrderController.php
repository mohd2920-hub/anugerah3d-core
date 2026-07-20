<?php

namespace App\Http\Controllers\Agent;

use App\Actions\Orders\PlaceAgentOrder;
use App\Actions\Orders\SendAgentOrderEmail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\StoreOrderRequest;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
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
