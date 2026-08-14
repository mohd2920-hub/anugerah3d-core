<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var Agent $agent */
        $agent = $request->user('agent');

        $orders = Order::query()
            ->whereBelongsTo($agent)
            ->with('items')
            ->latest('placed_at')
            ->get()
            ->map(fn (Order $order): array => $this->formatOrder($order));

        return view('agent.history', ['orders' => $orders]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOrder(Order $order): array
    {
        $status = Str::headline($order->status);
        $paymentMethod = $order->payment_method === 'pay_later' ? 'Pay later' : 'Bank transfer';
        $paymentStatus = $order->payment_status === 'paid' ? 'Paid' : 'Awaiting payment';

        return [
            'number' => $order->order_number,
            'date' => $order->placed_at->format('j M Y, g:i A'),
            'items' => $order->total_units,
            'amount' => (float) $order->total_amount,
            'status' => $status,
            'fulfilment' => $order->fulfilment_method === 'delivery' ? 'Delivery' : 'Self pickup',
            'payment' => "{$paymentMethod} - {$paymentStatus}",
            'recipient' => $order->recipient_name,
            'phone' => $order->phone_number,
            'address' => $order->delivery_address ?: 'Anugerah3D pickup counter',
            'notes' => $order->notes,
            'payment_proofs' => $order->paymentProofUrls(),
            'products' => $order->items->map(fn ($item): array => [
                'code' => $item->product_code,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => (float) $item->unit_price,
                'preorder' => $item->is_preorder,
                'clicker_character_count' => (int) ($item->clicker_character_count ?? 0),
                'clicker_characters' => $item->clickerCharactersText(),
            ])->all(),
            'timeline' => $this->timeline($order),
        ];
    }

    /**
     * @return Collection<int, array{label: string, complete: bool}>
     */
    private function timeline(Order $order): Collection
    {
        if ($order->status === Order::StatusCancelled) {
            return collect([
                ['label' => 'Order placed', 'complete' => true],
                ['label' => 'Order cancelled', 'complete' => true],
            ]);
        }

        $statusRank = match ($order->status) {
            Order::StatusCompleted => 3,
            Order::StatusProcessing => 2,
            default => 1,
        };

        return collect([
            ['label' => 'Order placed', 'complete' => true],
            ['label' => 'Payment confirmation', 'complete' => $order->payment_status === 'paid'],
            ['label' => 'Processing', 'complete' => $statusRank >= 2],
            ['label' => 'Completed', 'complete' => $statusRank >= 3],
        ]);
    }
}
