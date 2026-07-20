<?php

namespace App\Actions\Orders;

use App\Mail\Admin\AgentOrderPlacedMail;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\Order;
use App\Models\Product;
use App\Support\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PlaceAgentOrder
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Agent $agent, array $data, Request $request): Order
    {
        $existingOrder = Order::query()
            ->whereBelongsTo($agent)
            ->where('idempotency_key', $data['idempotency_key'])
            ->first();

        if ($existingOrder !== null) {
            return $this->notifyAdmins($existingOrder);
        }

        $order = DB::transaction(function () use ($agent, $data, $request): Order {
            $items = collect($data['items']);
            $products = Product::query()
                ->whereKey($items->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $this->ensureEveryProductExists($items, $products);

            $subtotalCents = 0;
            $totalUnits = 0;
            $orderItems = [];

            foreach ($items as $index => $item) {
                /** @var Product $product */
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];
                $isPreorder = $product->prd_balance <= 0;

                if (! $isPreorder && $quantity > $product->prd_balance) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Only {$product->prd_balance} units of {$product->prd_name} are available.",
                    ]);
                }

                $discountPercentage = (float) ($agent->discount_percentage > 0
                    ? $agent->discount_percentage
                    : $product->agent_discount_default);
                $sellingPriceCents = (int) round((float) $product->price_selling * 100);
                $discountTenths = (int) round($discountPercentage * 10);
                $unitPriceCents = (int) round($sellingPriceCents * (1000 - $discountTenths) / 1000);
                $lineTotalCents = $unitPriceCents * $quantity;

                $subtotalCents += $lineTotalCents;
                $totalUnits += $quantity;
                $orderItems[] = [
                    'product_id' => $product->getKey(),
                    'product_code' => $product->prd_code,
                    'product_name' => $product->prd_name,
                    'quantity' => $quantity,
                    'reserved_quantity' => $isPreorder ? 0 : $quantity,
                    'unit_selling_price' => $this->money($sellingPriceCents),
                    'discount_percentage' => number_format($discountPercentage, 1, '.', ''),
                    'unit_price' => $this->money($unitPriceCents),
                    'line_total' => $this->money($lineTotalCents),
                    'is_preorder' => $isPreorder,
                ];

                if (! $isPreorder) {
                    $product->decrement('prd_balance', $quantity);
                }
            }

            $order = Order::query()->create([
                'idempotency_key' => $data['idempotency_key'],
                'agent_id' => $agent->getKey(),
                'fulfilment_method' => $data['fulfilment_method'],
                'recipient_name' => $data['recipient_name'],
                'phone_number' => $data['phone_number'],
                'delivery_address' => $data['fulfilment_method'] === 'delivery' ? $data['delivery_address'] : null,
                'notes' => $data['notes'] ?? null,
                'payment_method' => $data['payment_method'],
                'subtotal' => $this->money($subtotalCents),
                'delivery_fee' => null,
                'total_amount' => $this->money($subtotalCents),
                'total_units' => $totalUnits,
                'placed_at' => now(),
            ]);

            $order->update([
                'order_number' => 'A3D-'.$order->placed_at->format('ymd').'-'.str_pad((string) $order->getKey(), 5, '0', STR_PAD_LEFT),
            ]);
            $order->items()->createMany($orderItems);
            $adminRecipientCount = AdminUser::query()->active()->count();

            AdminActivity::record(
                request: $request,
                event: 'agent.order.created',
                description: "Agent {$agent->agt_name} placed order {$order->order_number}.",
                properties: [
                    'page' => 'Agent Orders',
                    'agent_id' => $agent->getKey(),
                    'actor_name' => $agent->agt_name,
                    'email' => $agent->email,
                    'order_id' => $order->getKey(),
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'total_units' => $order->total_units,
                    'payment_method' => $order->payment_method,
                    'fulfilment_method' => $order->fulfilment_method,
                    'admin_recipient_count' => $adminRecipientCount,
                ],
            );

            return $order->load('items');
        }, 3);

        return $this->notifyAdmins($order);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @param  Collection<int, Product>  $products
     */
    private function ensureEveryProductExists(Collection $items, Collection $products): void
    {
        if ($items->count() !== $products->count()) {
            throw ValidationException::withMessages([
                'items' => 'One or more selected products are no longer available.',
            ]);
        }
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function notifyAdmins(Order $order): Order
    {
        if ($order->admin_notification_sent_at !== null) {
            return $order;
        }

        $adminEmails = AdminUser::query()->active()->orderBy('id')->pluck('email');

        if ($adminEmails->isEmpty()) {
            return $order;
        }

        Mail::to($adminEmails->all())->send(new AgentOrderPlacedMail(
            orderId: $order->getKey(),
            orderNumber: $order->order_number,
        ));

        $order->forceFill(['admin_notification_sent_at' => now()])->save();

        return $order;
    }
}
