<?php

namespace App\Actions\Orders;

use App\Models\Agent;
use App\Models\CustomerOrder;
use App\Models\Order;
use App\Models\Product;
use App\Support\AdminActivity;
use App\Support\CasingStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageAdminOrder
{
    public function process(Order $order, Request $request): Order
    {
        return DB::transaction(function () use ($order, $request): Order {
            $lockedOrder = $this->lockedOrder($order);
            $this->ensureStatus($lockedOrder, [Order::StatusPending], 'Only pending orders can be processed.');

            $items = $lockedOrder->items()->lockForUpdate()->get();
            $products = Product::query()
                ->whereKey($items->pluck('product_id')->sort()->values())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $item->setRelation('product', $products->get($item->product_id));
            }
            $shortages = app(CasingStock::class)->orderShortages($items)->map(fn (array $row): string => "{$row['product_name']}: requires {$row['required']}, available {$row['available']}");

            if ($shortages->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'stock' => 'Order cannot be processed due to insufficient product balance. '.$shortages->implode('; ').'.',
                ]);
            }

            foreach ($items as $item) {
                $required = $item->missingReservationQuantity();

                if ($required === 0) {
                    continue;
                }

                /** @var Product $product */
                $product = $products->get($item->product_id);
                $casingId = $item->getRawOriginal('clicker_casing_image_id');
                if ($casingId !== null) {
                    app(CasingStock::class)->move($product, (int) $casingId, (int) $item->clicker_character_count, -$required);
                } else {
                    $product->decrement('prd_balance', $required);
                }
                $item->update(['reserved_quantity' => $item->quantity]);
            }

            $lockedOrder->update([
                'status' => Order::StatusProcessing,
                'inventory_reserved_at' => $lockedOrder->inventory_reserved_at ?? now(),
                'processed_at' => now(),
            ]);

            $this->record(
                $request,
                $lockedOrder,
                'admin.order.processing',
                "Order {$lockedOrder->order_number} moved to processing.",
                ['from_status' => Order::StatusPending, 'to_status' => Order::StatusProcessing],
            );

            return $lockedOrder->load(['agent', 'items.product']);
        }, 3);
    }

    public function complete(Order $order, Request $request): Order
    {
        return DB::transaction(function () use ($order, $request): Order {
            $lockedOrder = $this->lockedOrder($order);
            $this->ensureStatus($lockedOrder, [Order::StatusProcessing], 'Only processing orders can be completed.');

            $lockedOrder->update([
                'status' => Order::StatusCompleted,
                'completed_at' => now(),
            ]);

            if (! $lockedOrder instanceof CustomerOrder) {
                Agent::query()->whereKey($lockedOrder->agent_id)->increment('total_sale', $lockedOrder->total_amount);
            }

            $this->record(
                $request,
                $lockedOrder,
                'admin.order.completed',
                "Order {$lockedOrder->order_number} completed.",
                ['from_status' => Order::StatusProcessing, 'to_status' => Order::StatusCompleted],
            );

            return $lockedOrder->load(['agent', 'items.product']);
        }, 3);
    }

    public function cancel(Order $order, Request $request): Order
    {
        return DB::transaction(function () use ($order, $request): Order {
            $lockedOrder = $this->lockedOrder($order);
            $this->ensureStatus(
                $lockedOrder,
                $lockedOrder instanceof CustomerOrder ? [Order::StatusPending, Order::StatusProcessing, 'ready', 'pickup_ready'] : [Order::StatusPending, Order::StatusProcessing],
                'Completed or cancelled orders cannot be cancelled.',
            );

            $fromStatus = $lockedOrder->status;
            $items = $lockedOrder->items()->lockForUpdate()->get();
            $products = Product::query()
                ->whereKey($items->pluck('product_id')->sort()->values())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $restoredUnits = 0;

            foreach ($items as $item) {
                if ($item->reserved_quantity === 0) {
                    continue;
                }

                /** @var Product $product */
                $product = $products->get($item->product_id);
                $casingId = $item->getRawOriginal('clicker_casing_image_id');
                if ($casingId !== null) {
                    app(CasingStock::class)->move($product, (int) $casingId, (int) $item->clicker_character_count, $item->reserved_quantity);
                } else {
                    $product->increment('prd_balance', $item->reserved_quantity);
                }
                $restoredUnits += $item->reserved_quantity;
                $item->update(['reserved_quantity' => 0]);
            }

            $lockedOrder->update([
                'status' => Order::StatusCancelled,
                'inventory_reserved_at' => null,
                'cancelled_at' => now(),
            ]);

            $this->record(
                $request,
                $lockedOrder,
                'admin.order.cancelled',
                "Order {$lockedOrder->order_number} cancelled and {$restoredUnits} reserved units restored.",
                [
                    'from_status' => $fromStatus,
                    'to_status' => Order::StatusCancelled,
                    'restored_units' => $restoredUnits,
                ],
            );

            return $lockedOrder->load(['agent', 'items.product']);
        }, 3);
    }

    public function updatePayment(Order $order, string $paymentStatus, Request $request): Order
    {
        return DB::transaction(function () use ($order, $paymentStatus, $request): Order {
            $lockedOrder = $this->lockedOrder($order);
            $fromPaymentStatus = $lockedOrder->payment_status;

            if ($fromPaymentStatus === $paymentStatus) {
                return $lockedOrder;
            }

            $lockedOrder->update(['payment_status' => $paymentStatus]);

            $this->record(
                $request,
                $lockedOrder,
                'admin.order.payment.updated',
                "Order {$lockedOrder->order_number} payment marked {$paymentStatus}.",
                [
                    'from_payment_status' => $fromPaymentStatus,
                    'to_payment_status' => $paymentStatus,
                ],
            );

            return $lockedOrder->load(['agent', 'items.product']);
        }, 3);
    }

    private function lockedOrder(Order $order): Order
    {
        return $order->newQuery()->lockForUpdate()->findOrFail($order->getKey());
    }

    /**
     * @param  array<int, string>  $allowedStatuses
     */
    private function ensureStatus(Order $order, array $allowedStatuses, string $message): void
    {
        if (! in_array($order->status, $allowedStatuses, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function record(
        Request $request,
        Order $order,
        string $event,
        string $description,
        array $properties,
    ): void {
        AdminActivity::record(
            request: $request,
            event: $event,
            description: $description,
            adminUser: $request->user('admin'),
            properties: array_merge([
                'page' => $order instanceof CustomerOrder ? 'Customer Orders' : 'Orders',
                'order_id' => $order->getKey(),
                'order_number' => $order->order_number,
            ], $properties),
        );
    }
}
