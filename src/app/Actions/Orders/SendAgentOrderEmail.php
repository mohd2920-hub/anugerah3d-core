<?php

namespace App\Actions\Orders;

use App\Mail\Agent\OrderUpdateMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class SendAgentOrderEmail
{
    public function handle(Order $order, string $updateType): bool
    {
        if (! config('order_notifications.agent_email_enabled')) {
            return false;
        }

        $order->loadMissing('agent:id,agt_name,email');

        Mail::to($order->agent->email)->send(new OrderUpdateMail(
            orderId: $order->getKey(),
            orderNumber: $order->order_number,
            updateType: $updateType,
        ));

        return true;
    }
}
