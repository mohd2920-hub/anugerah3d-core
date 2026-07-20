<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\ManageAdminOrder;
use App\Actions\Orders\SendAgentOrderEmail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManageOrderRequest;
use App\Http\Requests\Admin\UpdateOrderPaymentRequest;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private ManageAdminOrder $manageAdminOrder,
        private SendAgentOrderEmail $sendAgentOrderEmail,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->trim()->toString(),
            'payment_status' => $request->string('payment_status')->trim()->toString(),
            'fulfilment_method' => $request->string('fulfilment_method')->trim()->toString(),
        ];

        $orders = Order::query()
            ->with([
                'agent:id,agt_name,login_id,email,phone_number',
                'items:id,order_id,product_id,quantity,reserved_quantity,is_preorder',
                'items.product:id,prd_balance',
            ])
            ->withCount('items')
            ->when($filters['search'] !== '', fn (Builder $query): Builder => $query->search($filters['search']))
            ->when(in_array($filters['status'], $this->statuses(), true), fn (Builder $query): Builder => $query->where('status', $filters['status']))
            ->when(in_array($filters['payment_status'], $this->paymentStatuses(), true), fn (Builder $query): Builder => $query->where('payment_status', $filters['payment_status']))
            ->when(in_array($filters['fulfilment_method'], ['delivery', 'pickup'], true), fn (Builder $query): Builder => $query->where('fulfilment_method', $filters['fulfilment_method']))
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString();

        $orders->getCollection()->each(function (Order $order): void {
            $order->setAttribute('has_stock_shortage', $order->stockShortages()->isNotEmpty());
        });

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => $this->statuses(),
            'paymentStatuses' => $this->paymentStatuses(),
            'summary' => [
                'total' => Order::query()->count(),
                'pending' => Order::query()->where('status', Order::StatusPending)->count(),
                'processing' => Order::query()->where('status', Order::StatusProcessing)->count(),
                'completed' => Order::query()->where('status', Order::StatusCompleted)->count(),
            ],
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'agent:id,agt_name,login_id,email,phone_number,address,city,state',
            'items.product:id,prd_code,prd_name,prd_balance,material_id,material,color',
            'items.product.materialType:id,name',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'stockShortages' => $order->stockShortages(),
            'paymentStatuses' => $this->paymentStatuses(),
        ]);
    }

    public function process(ManageOrderRequest $request, Order $order): RedirectResponse
    {
        $updatedOrder = $this->manageAdminOrder->process($order, $request);
        $this->sendAgentOrderEmail->handle($updatedOrder, 'processing');

        return back()->with('success', "Order {$order->order_number} is now processing.");
    }

    public function complete(ManageOrderRequest $request, Order $order): RedirectResponse
    {
        $updatedOrder = $this->manageAdminOrder->complete($order, $request);
        $this->sendAgentOrderEmail->handle($updatedOrder, 'completed');

        return back()->with('success', "Order {$order->order_number} completed successfully.");
    }

    public function cancel(ManageOrderRequest $request, Order $order): RedirectResponse
    {
        $updatedOrder = $this->manageAdminOrder->cancel($order, $request);
        $this->sendAgentOrderEmail->handle($updatedOrder, 'cancelled');

        return back()->with('success', "Order {$order->order_number} has been cancelled.");
    }

    public function updatePayment(UpdateOrderPaymentRequest $request, Order $order): RedirectResponse
    {
        $previousPaymentStatus = $order->payment_status;
        $updatedOrder = $this->manageAdminOrder->updatePayment(
            $order,
            $request->validated('payment_status'),
            $request,
        );

        if ($updatedOrder->payment_status !== $previousPaymentStatus) {
            $this->sendAgentOrderEmail->handle($updatedOrder, 'payment_updated');
        }

        return back()->with('success', "Payment status for {$order->order_number} updated.");
    }

    /**
     * @return array<int, string>
     */
    private function statuses(): array
    {
        return [
            Order::StatusPending,
            Order::StatusProcessing,
            Order::StatusCompleted,
            Order::StatusCancelled,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function paymentStatuses(): array
    {
        return [
            Order::PaymentStatusUnpaid,
            Order::PaymentStatusPaid,
            Order::PaymentStatusRefunded,
        ];
    }
}
