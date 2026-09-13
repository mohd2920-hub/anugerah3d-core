<?php

namespace App\Actions\Orders;

use App\Models\CustomerOrder;
use Illuminate\Validation\ValidationException;

class UpdateCustomerOrderProgress
{
    public function handle(CustomerOrder $order, string $action, array $data): void
    {
        $allowed = match ($action) {
            'ready' => ['processing'], 'ship' => ['ready'], 'pickup_ready' => ['ready'],
            'complete' => ['processing', 'ready', 'shipped', 'pickup_ready'], default => [],
        };
        if (! in_array($order->status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'Perubahan status tidak dibenarkan daripada status semasa.']);
        }
        if (($action === 'ship' && $order->fulfilment_method !== 'delivery') || ($action === 'pickup_ready' && $order->fulfilment_method !== 'pickup')) {
            throw ValidationException::withMessages(['status' => 'Status tidak sepadan dengan kaedah penghantaran.']);
        }
        if ($action === 'ship' && $order->payment_status !== 'paid') {
            throw ValidationException::withMessages(['payment_status' => 'Sahkan bayaran sebelum menandakan pesanan dihantar.']);
        }
        [$status,$timestamp] = match ($action) {
            'ready' => ['ready', 'ready_at'], 'ship' => ['shipped', 'shipped_at'],
            'pickup_ready' => ['pickup_ready', 'pickup_ready_at'], 'complete' => ['completed', 'completed_at'],
        };
        $attributes = ['status' => $status, $timestamp => now()];
        if ($action === 'ship') {
            $attributes += ['courier' => $data['courier'], 'tracking_number' => $data['tracking_number']];
        }
        $order->forceFill($attributes)->save();
    }

    public function issueReceipt(CustomerOrder $order): void
    {
        if ($order->payment_status !== 'paid' || $order->receipt_number) {
            return;
        }
        $issuedAt = now();
        $order->forceFill([
            'receipt_number' => 'R-'.$order->order_number,
            'payment_confirmed_at' => $issuedAt,
            'receipt_snapshot' => [
                'recipient_name' => $order->recipient_name,
                'order_number' => $order->order_number,
                'issued_at' => $issuedAt->format('d/m/Y H:i'),
                'subtotal' => $order->subtotal, 'delivery_fee' => $order->deliveryFeeAmount(), 'total' => $order->total_amount,
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->product_name, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price, 'total' => $item->line_total, 'characters' => $item->clickerCharactersText(),
                ])->all(),
            ],
        ])->save();
    }
}
