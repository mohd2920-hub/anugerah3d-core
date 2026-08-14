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
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
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
                'items:id,order_id,product_id,product_name,quantity,reserved_quantity,is_preorder',
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
        $data = $this->orderViewData($order);

        return view('admin.orders.show', $data + [
            'paymentStatuses' => $this->paymentStatuses(),
        ]);
    }

    public function printFull(Order $order): Response
    {
        $data = $this->orderViewData($order);

        return response($this->renderFullPrintDocument($data['order'], $data['stockShortages']));
    }

    public function printOrder(Order $order): Response
    {
        $data = $this->orderViewData($order);

        return response($this->renderCompactOrderPrintDocument($data['order']));
    }

    /**
     * @return array{order: Order, stockShortages: Collection<int, array{product_name: string, required: int, available: int}>}
     */
    private function orderViewData(Order $order): array
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

        return [
            'order' => $order,
            'stockShortages' => $order->stockShortages(),
        ];
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

    private function escape(?string $value): string
    {
        return e((string) ($value ?? ''));
    }

    private function formatMoney(float|int|string|null $value): string
    {
        return 'RM '.number_format((float) $value, 2);
    }

    private function agentAddress(Order $order): string
    {
        return collect([$order->agent->address, $order->agent->city, $order->agent->state])
            ->filter(fn (?string $value): bool => filled($value))
            ->implode(', ') ?: '-';
    }

    private function renderPrintLayout(string $title, string $pageSize, string $body, string $pageClass = ''): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
    <style>
        @page { size: {$pageSize}; margin: 12mm; }
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e2e8f0; color: #0f172a; font-family: "Segoe UI", Arial, sans-serif; }
        .screen-toolbar { position: sticky; top: 0; z-index: 10; display: flex; justify-content: flex-end; gap: 12px; padding: 16px 20px; background: rgba(15, 23, 42, 0.92); }
        .screen-toolbar button { border: 0; border-radius: 999px; background: #ffffff; color: #0f172a; padding: 10px 16px; font: inherit; font-weight: 600; cursor: pointer; }
        .sheet { width: min(1200px, calc(100vw - 32px)); margin: 24px auto; background: #ffffff; padding: 24px; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.14); }
        .brand-strip, .hero { display: flex; justify-content: space-between; gap: 18px; align-items: flex-start; }
        .brand-mark { display: grid; place-items: center; width: 54px; height: 54px; border-radius: 16px; background: #0f172a; color: #ffffff; font-weight: 700; letter-spacing: 0.14em; }
        .brand-logo { display: block; width: 72px; height: 72px; border-radius: 14px; object-fit: cover; }
        .eyebrow { font-size: 12px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: #475569; }
        h1, h2, h3, p { margin: 0; }
        h1 { margin-top: 6px; font-size: 28px; }
        h2 { font-size: 16px; }
        .muted { color: #64748b; }
        .hero-total { text-align: right; }
        .hero-total strong { display: block; font-size: 30px; margin-top: 6px; }
        .grid-two { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 20px; }
        .grid-three { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-top: 20px; }
        .card { border: 1px solid #cbd5e1; border-radius: 18px; padding: 16px; background: #ffffff; }
        .list-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 16px; margin-top: 14px; }
        .list-grid div span { display: block; }
        .label { font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b; }
        .value { margin-top: 4px; font-size: 14px; line-height: 1.55; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #cbd5e1; padding: 9px 10px; vertical-align: top; font-size: 12px; }
        th { background: #f8fafc; text-align: left; }
        .align-right { text-align: right; }
        .item-name { font-weight: 700; }
        .item-subtle { margin-top: 4px; color: #64748b; font-size: 11px; }
        .item-char-group { margin-top: 6px; }
        .item-char-label { display: inline-block; border-radius: 999px; background: #e2e8f0; padding: 2px 8px; color: #334155; font-size: 10px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; vertical-align: middle; }
        .item-char-row { display: block; margin-top: 4px; white-space: nowrap; }
        .item-char-chip { display: inline-block; width: 18px; height: 18px; line-height: 18px; border-radius: 6px; border: 1px solid #fdba74; background: linear-gradient(180deg, #fff7ed, #ffedd5); color: #c2410c; font-size: 10px; font-weight: 800; text-align: center; margin-right: 3px; }
        .item-char-chip:last-child { margin-right: 0; }
        .note-box { margin-top: 18px; border-radius: 18px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 14px 16px; }
        .totals { margin-top: 18px; display: grid; gap: 8px; justify-content: end; }
        .totals-row { display: flex; justify-content: space-between; gap: 28px; min-width: 260px; }
        .totals-row strong { font-size: 16px; }
        .timeline { margin-top: 18px; display: grid; gap: 10px; }
        .timeline-row { padding-left: 14px; border-left: 3px solid #cbd5e1; }
        .section-stack > * + * { margin-top: 18px; }
        .status-pill { display: inline-flex; margin-top: 10px; margin-right: 8px; border-radius: 999px; background: #e2e8f0; padding: 6px 10px; font-size: 11px; font-weight: 700; color: #334155; }
        .compact .sheet { width: min(760px, calc(100vw - 24px)); padding: 18px; }
        .compact h1 { font-size: 22px; }
        .compact th, .compact td { font-size: 11px; padding: 7px 8px; }
        .compact .list-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media print {
            body { background: #ffffff; }
            .screen-toolbar { display: none; }
            .sheet { width: auto; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body class="{$pageClass}">
    <div class="screen-toolbar">
        <button type="button" onclick="window.print()">Print PDF</button>
        <button type="button" onclick="window.close()">Close</button>
    </div>
    <main class="sheet">
{$body}
    </main>
    <script>
        window.addEventListener("load", function () {
            window.setTimeout(function () {
                window.print();
            }, 120);
        });
    </script>
</body>
</html>
HTML;
    }

    private function renderCompactOrderPrintDocument(Order $order): string
    {
        $orderNumber = $this->escape((string) $order->order_number);
        $placedAt = $this->escape($order->placed_at->format('d M Y, h:i A'));
        $recipientName = $this->escape($order->recipient_name);
        $agentName = $this->escape($order->agent->agt_name);
        $agentId = $this->escape($order->agent->login_id ?: '-');
        $phone = $this->escape($order->phone_number ?: '-');
        $address = $this->escape($order->delivery_address ?: 'Anugerah3D pickup counter');
        $notes = $this->escape($order->notes ?: 'No notes provided.');
        $logoUrl = $this->escape(asset('images/anugerah3d-logo.png'));
        $grossSubtotal = $this->formatMoney($order->grossSubtotalAmount());
        $discountAmount = $this->formatMoney($order->discountAmount());
        $discountPercentage = number_format($order->effectiveDiscountPercentage(), 1).'%';
        $subtotal = $this->formatMoney($order->subtotal);
        $deliveryFee = $order->delivery_fee === null ? 'To be confirmed' : $this->formatMoney($order->delivery_fee);
        $total = $this->formatMoney($order->total_amount);

        $rows = $order->items->map(function ($item): string {
            $productName = $this->escape($item->product_name);
            $productCode = $this->escape($item->product_code);
            $clickerLine = $this->renderClickerCharactersLine($item);
            $quantity = (string) $item->quantity;
            $price = $this->formatMoney($item->unit_selling_price);
            $discount = number_format((float) $item->discount_percentage, 1).'%';
            $unitPrice = $this->formatMoney($item->unit_price);
            $lineTotal = $this->formatMoney($item->line_total);

            return <<<HTML
            <tr>
                <td>
                    <div class="item-name">{$productName}</div>
                    <div class="item-subtle">{$productCode}</div>
                    {$clickerLine}
                </td>
                <td class="align-right">{$quantity}</td>
                <td class="align-right">{$price}</td>
                <td class="align-right">{$discount}</td>
                <td class="align-right">{$unitPrice}</td>
                <td class="align-right">{$lineTotal}</td>
            </tr>
            HTML;
        })->implode('');

        $body = <<<HTML
        <section class="brand-strip">
            <img src="{$logoUrl}" alt="Anugerah3D" class="brand-logo">
            <div>
                <div class="eyebrow">Anugerah3D</div>
                <h1>Print order</h1>
                <p class="muted">Order {$orderNumber} | {$placedAt}</p>
            </div>
        </section>

        <section class="card" style="margin-top: 18px;">
            <div class="list-grid">
                <div><span class="label">Name</span><span class="value">{$recipientName}</span></div>
                <div><span class="label">Agent</span><span class="value">{$agentName}</span></div>
                <div><span class="label">Agent ID</span><span class="value">{$agentId}</span></div>
                <div><span class="label">Phone</span><span class="value">{$phone}</span></div>
                <div style="grid-column: 1 / -1;"><span class="label">Address</span><span class="value">{$address}</span></div>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Items</th>
                    <th class="align-right">Qty</th>
                    <th class="align-right">Selling</th>
                    <th class="align-right">Discount</th>
                    <th class="align-right">Price</th>
                    <th class="align-right">Total price</th>
                </tr>
            </thead>
            <tbody>
                {$rows}
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row"><span>Gross subtotal</span><span>{$grossSubtotal}</span></div>
            <div class="totals-row"><span>Eligible discount ({$discountPercentage})</span><span>- {$discountAmount}</span></div>
            <div class="totals-row"><span>Products subtotal</span><span>{$subtotal}</span></div>
            <div class="totals-row"><span>Delivery fee</span><span>{$deliveryFee}</span></div>
            <div class="totals-row"><strong>Order total</strong><strong>{$total}</strong></div>
        </div>

        <section class="note-box">
            <div class="label">Order note</div>
            <div class="value">{$notes}</div>
        </section>
        HTML;

        return $this->renderPrintLayout("Print order {$order->order_number}", 'A5 portrait', $body, 'compact');
    }

    private function renderFullPrintDocument(Order $order, Collection $stockShortages): string
    {
        $orderNumber = $this->escape((string) $order->order_number);
        $placedAt = $this->escape($order->placed_at->format('d M Y, h:i A'));
        $status = $this->escape($order->statusLabel());
        $paymentStatus = $this->escape($order->paymentStatusLabel());
        $paymentMethod = $this->escape($order->paymentMethodLabel());
        $recipientName = $this->escape($order->recipient_name);
        $recipientPhone = $this->escape($order->phone_number ?: '-');
        $fulfilmentMethod = $this->escape($order->fulfilmentLabel());
        $deliveryAddress = $this->escape($order->delivery_address ?: 'Anugerah3D pickup counter');
        $notes = $this->escape($order->notes ?: 'No notes provided.');
        $agentName = $this->escape($order->agent->agt_name);
        $agentId = $this->escape($order->agent->login_id ?: '-');
        $agentEmail = $this->escape($order->agent->email ?: '-');
        $agentPhone = $this->escape($order->agent->phone_number ?: '-');
        $agentAddress = $this->escape($this->agentAddress($order));
        $grossSubtotal = $this->formatMoney($order->grossSubtotalAmount());
        $discountAmount = $this->formatMoney($order->discountAmount());
        $discountPercentage = number_format($order->effectiveDiscountPercentage(), 1).'%';
        $subtotal = $this->formatMoney($order->subtotal);
        $deliveryFee = $order->delivery_fee === null ? 'To be confirmed' : $this->formatMoney($order->delivery_fee);
        $orderTotal = $this->formatMoney($order->total_amount);
        $totalCost = $this->formatMoney($order->total_cost);
        $bonusTotal = $this->formatMoney($order->bonus_total);
        $profitAmount = $this->formatMoney($order->profit_amount);
        $stockMessage = $stockShortages->isEmpty()
            ? 'All items have enough stock for processing.'
            : 'Stock shortages found for '.$stockShortages->count().' item(s).';
        $paymentProofMessage = count($order->paymentProofUrls()) > 0
            ? count($order->paymentProofUrls()).' payment proof file(s) uploaded.'
            : 'No payment proof uploaded.';

        $itemRows = $order->items->map(function ($item): string {
            $productName = $this->escape($item->product_name);
            $productCode = $this->escape($item->product_code);
            $clickerLine = $this->renderClickerCharactersLine($item);
            $quantity = (string) $item->quantity;
            $price = $this->formatMoney($item->unit_selling_price);
            $discount = number_format((float) $item->discount_percentage, 1).'%';
            $unitPrice = $this->formatMoney($item->unit_price);
            $lineTotal = $this->formatMoney($item->line_total);
            $reserved = (string) $item->reserved_quantity;
            $balance = (string) max(0, (int) ($item->product?->prd_balance ?? 0));
            $missing = $item->missingReservationQuantity();
            $readiness = $missing === 0 ? 'Reserved' : ($missing > (int) $balance ? 'Short' : 'Pending');

            return <<<HTML
            <tr>
                <td>
                    <div class="item-name">{$productName}</div>
                    <div class="item-subtle">{$productCode}</div>
                    {$clickerLine}
                </td>
                <td class="align-right">{$quantity}</td>
                <td class="align-right">{$price}</td>
                <td class="align-right">{$discount}</td>
                <td class="align-right">{$unitPrice}</td>
                <td class="align-right">{$lineTotal}</td>
                <td class="align-right">{$reserved}</td>
                <td class="align-right">{$balance}</td>
                <td>{$readiness}</td>
            </tr>
            HTML;
        })->implode('');

        $shortageRows = $stockShortages->map(function (array $shortage): string {
            $product = $this->escape($shortage['product_name']);
            $required = (string) $shortage['required'];
            $available = (string) $shortage['available'];

            return "<tr><td>{$product}</td><td class='align-right'>{$required}</td><td class='align-right'>{$available}</td></tr>";
        })->implode('');

        $timelineRows = collect([
            ['Order placed', $order->placed_at],
            ['Inventory reserved', $order->inventory_reserved_at],
            ['Processing started', $order->processed_at],
            ['Order completed', $order->completed_at],
            ['Order cancelled', $order->cancelled_at],
        ])->filter(fn (array $entry): bool => $entry[1] !== null)
            ->map(function (array $entry): string {
                $label = $this->escape($entry[0]);
                $value = $this->escape($entry[1]->format('d M Y, h:i A'));

                return "<div class='timeline-row'><div class='item-name'>{$label}</div><div class='item-subtle'>{$value}</div></div>";
            })->implode('');

        $shortageSection = $stockShortages->isEmpty() ? '' : <<<HTML
        <section class="card">
            <h2>Stock shortages</h2>
            <p class="muted" style="margin-top: 6px;">Items that still need additional stock before processing.</p>
            <table>
                <thead>
                    <tr><th>Product</th><th class="align-right">Required</th><th class="align-right">Available</th></tr>
                </thead>
                <tbody>{$shortageRows}</tbody>
            </table>
        </section>
        HTML;

        $body = <<<HTML
        <section class="hero">
            <div>
                <div class="eyebrow">Anugerah3D admin print</div>
                <h1>Order {$orderNumber}</h1>
                <p class="muted" style="margin-top: 8px;">Placed {$placedAt}</p>
                <div class="status-pill">{$status}</div>
                <div class="status-pill">{$paymentStatus}</div>
                <div class="status-pill">{$paymentMethod}</div>
            </div>
            <div class="hero-total">
                <div class="eyebrow">Order total</div>
                <strong>{$orderTotal}</strong>
                <p class="muted" style="margin-top: 6px;">{$stockMessage}</p>
            </div>
        </section>

        <div class="grid-three">
            <section class="card">
                <h2>Recipient and fulfilment</h2>
                <div class="list-grid">
                    <div><span class="label">Recipient</span><span class="value">{$recipientName}</span></div>
                    <div><span class="label">Phone</span><span class="value">{$recipientPhone}</span></div>
                    <div><span class="label">Method</span><span class="value">{$fulfilmentMethod}</span></div>
                    <div style="grid-column: 1 / -1;"><span class="label">Address</span><span class="value">{$deliveryAddress}</span></div>
                </div>
            </section>
            <section class="card">
                <h2>Agent</h2>
                <div class="list-grid">
                    <div><span class="label">Name</span><span class="value">{$agentName}</span></div>
                    <div><span class="label">Agent ID</span><span class="value">{$agentId}</span></div>
                    <div><span class="label">Email</span><span class="value">{$agentEmail}</span></div>
                    <div><span class="label">Phone</span><span class="value">{$agentPhone}</span></div>
                    <div style="grid-column: 1 / -1;"><span class="label">Address</span><span class="value">{$agentAddress}</span></div>
                </div>
            </section>
            <section class="card">
                <h2>Financial summary</h2>
                <div class="list-grid">
                    <div><span class="label">Gross subtotal</span><span class="value">{$grossSubtotal}</span></div>
                    <div><span class="label">Eligible discount</span><span class="value">- {$discountAmount} ({$discountPercentage})</span></div>
                    <div><span class="label">Products subtotal</span><span class="value">{$subtotal}</span></div>
                    <div><span class="label">Delivery fee</span><span class="value">{$deliveryFee}</span></div>
                    <div><span class="label">Total cost</span><span class="value">{$totalCost}</span></div>
                    <div><span class="label">Bonus total</span><span class="value">{$bonusTotal}</span></div>
                    <div><span class="label">Profit</span><span class="value">{$profitAmount}</span></div>
                    <div><span class="label">Payment proof</span><span class="value">{$paymentProofMessage}</span></div>
                </div>
            </section>
        </div>

        <section class="section-stack" style="margin-top: 20px;">
            <section class="card">
                <h2>Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="align-right">Qty</th>
                            <th class="align-right">Selling</th>
                            <th class="align-right">Discount</th>
                            <th class="align-right">Price</th>
                            <th class="align-right">Total price</th>
                            <th class="align-right">Reserved</th>
                            <th class="align-right">Balance</th>
                            <th>Readiness</th>
                        </tr>
                    </thead>
                    <tbody>{$itemRows}</tbody>
                </table>
                <div class="totals">
                    <div class="totals-row"><span>Gross subtotal</span><span>{$grossSubtotal}</span></div>
                    <div class="totals-row"><span>Eligible discount ({$discountPercentage})</span><span>- {$discountAmount}</span></div>
                    <div class="totals-row"><span>Products subtotal</span><span>{$subtotal}</span></div>
                    <div class="totals-row"><span>Delivery fee</span><span>{$deliveryFee}</span></div>
                    <div class="totals-row"><strong>Order total</strong><strong>{$orderTotal}</strong></div>
                </div>
            </section>
            {$shortageSection}
            <section class="grid-two">
                <section class="card">
                    <h2>Order note</h2>
                    <p class="value">{$notes}</p>
                </section>
                <section class="card">
                    <h2>Timeline</h2>
                    <div class="timeline">{$timelineRows}</div>
                </section>
            </section>
        </section>
        HTML;

        return $this->renderPrintLayout("Print full {$order->order_number}", 'A4 landscape', $body);
    }

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

    private function renderClickerCharactersLine(object $item): string
    {
        $clickerCount = (int) ($item->clicker_character_count ?? 0);
        if ($clickerCount <= 0) {
            return '';
        }

        $characters = collect($item->clicker_characters ?? [])
            ->map(fn (mixed $character): string => strtoupper(trim((string) $character)))
            ->filter()
            ->values();

        if ($characters->isEmpty()) {
            $fromText = strtoupper((string) ($item->clickerCharactersText() ?? ''));
            $characters = collect(mb_str_split($fromText))
                ->map(fn (string $character): string => trim($character))
                ->filter();
        }

        $chips = $characters
            ->take($clickerCount)
            ->map(fn (string $character): string => "<span class='item-char-chip'>{$this->escape($character)}</span>")
            ->implode('');

        if ($chips === '') {
            $chips = "<span class='item-subtle'>-</span>";
        }

        return "<div class='item-char-group'><span class='item-char-label'>Characters ({$clickerCount})</span><span class='item-char-row'>{$chips}</span></div>";
    }
}
