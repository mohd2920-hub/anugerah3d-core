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
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    public function __construct(
        private ManageAdminOrder $manageAdminOrder,
        private SendAgentOrderEmail $sendAgentOrderEmail,
    ) {}

    public function index(Request $request): View
    {
        $hasTier1Column = Schema::hasColumn('usr_agent', 'tier1_percentage');
        $hasTier2Column = Schema::hasColumn('usr_agent', 'tier2_percentage');

        $agentSelect = ['id', 'referrer_id', 'agt_name', 'login_id', 'email', 'phone_number'];
        $uplineSelect = ['id', 'referrer_id', 'agt_name'];
        $tier2UplineSelect = ['id', 'agt_name'];
        if ($hasTier1Column) {
            $uplineSelect[] = 'tier1_percentage';
        }
        if ($hasTier2Column) {
            $tier2UplineSelect[] = 'tier2_percentage';
        }

        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->trim()->toString(),
            'payment_status' => $request->string('payment_status')->trim()->toString(),
            'fulfilment_method' => $request->string('fulfilment_method')->trim()->toString(),
        ];

        $orders = Order::query()
            ->with([
                'agent' => fn ($query) => $query
                    ->select($agentSelect)
                    ->with([
                        'referrer' => fn ($query) => $query
                            ->select($uplineSelect)
                            ->with([
                                'referrer' => fn ($query) => $query->select($tier2UplineSelect),
                            ]),
                    ]),
                'items:id,order_id,product_id,quantity,reserved_quantity,is_preorder',
                'items.product:id,prd_balance,cost_rm',
            ])
            ->withCount('items')
            ->when($filters['search'] !== '', fn (Builder $query): Builder => $query->search($filters['search']))
            ->when(in_array($filters['status'], $this->statuses(), true), fn (Builder $query): Builder => $query->where('status', $filters['status']))
            ->when(in_array($filters['payment_status'], $this->paymentStatuses(), true), fn (Builder $query): Builder => $query->where('payment_status', $filters['payment_status']))
            ->when(in_array($filters['fulfilment_method'], ['delivery', 'pickup'], true), fn (Builder $query): Builder => $query->where('fulfilment_method', $filters['fulfilment_method']))
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString();

        $orders->getCollection()->each(function (Order $order) use ($hasTier1Column, $hasTier2Column): void {
            $order->setAttribute('has_stock_shortage', $order->stockShortages()->isNotEmpty());
            $this->decorateOrderFinancials($order, $hasTier1Column, $hasTier2Column);
        });

        $summaryOrders = Order::query()
            ->whereIn('status', [Order::StatusPending, Order::StatusProcessing, Order::StatusCompleted])
            ->with([
                'agent' => fn ($query) => $query
                    ->select($agentSelect)
                    ->with([
                        'referrer' => fn ($query) => $query
                            ->select($uplineSelect)
                            ->with([
                                'referrer' => fn ($query) => $query->select($tier2UplineSelect),
                            ]),
                    ]),
                'items:id,order_id,product_id,quantity',
                'items.product:id,cost_rm',
            ])
            ->get(['id', 'agent_id', 'status', 'payment_status', 'subtotal']);

        $totalProfit = 0.0;
        $bonusPaid = 0.0;
        $bonusPending = 0.0;

        foreach ($summaryOrders as $summaryOrder) {
            $numbers = $this->computeOrderFinancials($summaryOrder, $hasTier1Column, $hasTier2Column);
            $totalProfit += $numbers['profit_amount'];

            if ($summaryOrder->payment_status === Order::PaymentStatusPaid) {
                $bonusPaid += $numbers['bonus_total'];
            }

            if ($summaryOrder->payment_status === Order::PaymentStatusUnpaid) {
                $bonusPending += $numbers['bonus_total'];
            }
        }

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
                'completed_sales_amount' => (float) Order::query()->where('status', Order::StatusCompleted)->sum('total_amount'),
                'total_profit' => round($totalProfit, 2),
                'bonus_paid' => round($bonusPaid, 2),
                'bonus_pending' => round($bonusPending, 2),
            ],
        ]);
    }

    public function show(Order $order): View
    {
        $hasTier1Column = Schema::hasColumn('usr_agent', 'tier1_percentage');
        $hasTier2Column = Schema::hasColumn('usr_agent', 'tier2_percentage');

        $agentSelect = ['id', 'referrer_id', 'agt_name', 'login_id', 'email', 'phone_number', 'address', 'city', 'state'];
        $uplineSelect = ['id', 'referrer_id', 'agt_name'];
        $tier2UplineSelect = ['id', 'agt_name'];
        if ($hasTier1Column) {
            $uplineSelect[] = 'tier1_percentage';
        }
        if ($hasTier2Column) {
            $tier2UplineSelect[] = 'tier2_percentage';
        }

        $order->load([
            'agent' => fn ($query) => $query
                ->select($agentSelect)
                ->with([
                    'referrer' => fn ($query) => $query
                        ->select($uplineSelect)
                        ->with([
                            'referrer' => fn ($query) => $query->select($tier2UplineSelect),
                        ]),
                ]),
            'items.product:id,prd_code,prd_name,prd_balance,material_id,material,color,cost_rm,prd_picture',
            'items.product.materialType:id,name',
        ]);
        $this->decorateOrderFinancials($order, $hasTier1Column, $hasTier2Column);

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

    private function decorateOrderFinancials(Order $order, bool $hasTier1Column, bool $hasTier2Column): void
    {
        $numbers = $this->computeOrderFinancials($order, $hasTier1Column, $hasTier2Column);

        $order->setAttribute('total_cost', $numbers['total_cost']);
        $order->setAttribute('tier1_bonus_rate', $numbers['tier1_bonus_rate']);
        $order->setAttribute('tier2_bonus_rate', $numbers['tier2_bonus_rate']);
        $order->setAttribute('tier1_bonus', $numbers['tier1_bonus']);
        $order->setAttribute('tier2_bonus', $numbers['tier2_bonus']);
        $order->setAttribute('bonus_total', $numbers['bonus_total']);
        $order->setAttribute('gross_profit_amount', $numbers['gross_profit_amount']);
        $order->setAttribute('profit_amount', $numbers['profit_amount']);
    }

    /** @return array<string, float> */
    private function computeOrderFinancials(Order $order, bool $hasTier1Column, bool $hasTier2Column): array
    {
        $subtotal = (float) $order->subtotal;
        $totalCost = $order->items->sum(function ($item): float {
            return (float) ($item->product?->cost_rm ?? 0) * (int) $item->quantity;
        });

        $tier1Upline = $order->agent?->referrer;
        $tier2Upline = $tier1Upline?->referrer;

        $tier1Rate = $hasTier1Column ? (float) ($tier1Upline?->tier1_percentage ?? 7) : 7.0;
        $tier2Rate = $hasTier2Column ? (float) ($tier2Upline?->tier2_percentage ?? 3) : 3.0;

        $tier1Bonus = $tier1Upline ? round($subtotal * $tier1Rate / 100, 2) : 0.0;
        $tier2Bonus = $tier2Upline ? round($subtotal * $tier2Rate / 100, 2) : 0.0;
        $bonusTotal = round($tier1Bonus + $tier2Bonus, 2);
        $grossProfit = round($subtotal - $totalCost, 2);
        $netProfit = round($grossProfit - $bonusTotal, 2);

        return [
            'total_cost' => round($totalCost, 2),
            'tier1_bonus_rate' => $tier1Rate,
            'tier2_bonus_rate' => $tier2Rate,
            'tier1_bonus' => $tier1Bonus,
            'tier2_bonus' => $tier2Bonus,
            'bonus_total' => $bonusTotal,
            'gross_profit_amount' => $grossProfit,
            'profit_amount' => $netProfit,
        ];
    }
}
