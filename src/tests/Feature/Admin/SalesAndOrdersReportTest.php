<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\Order;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSession;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SalesAndOrdersReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sales_report_applies_date_range_to_rows_and_financial_summary(): void
    {
        $admin = AdminUser::factory()->create();
        [$agent, $site, $operation, $session] = $this->posContext();
        $product = Product::factory()->create(['cost_rm' => 10]);

        $includedSale = $this->createSale(
            agent: $agent,
            site: $site,
            operation: $operation,
            session: $session,
            product: $product,
            number: 'POS-IN-RANGE',
            soldAt: '2026-08-10 12:00:00',
            amount: 80,
            quantity: 2,
        );
        $this->createSale(
            agent: $agent,
            site: $site,
            operation: $operation,
            session: $session,
            product: $product,
            number: 'POS-OUT-RANGE',
            soldAt: '2026-08-11 12:00:00',
            amount: 50,
            quantity: 1,
        );

        $response = $this->actingAs($admin, 'admin')->get(route('admin.sales.index', [
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
        ]));

        $summary = $response->viewData('summary');

        $response->assertOk()
            ->assertSeeText($includedSale->sale_number)
            ->assertDontSeeText('POS-OUT-RANGE')
            ->assertSeeText('Advanced sales summary')
            ->assertSeeText('Start date')
            ->assertSeeText('End date');
        $this->assertSame(1, $summary['transaction_count']);
        $this->assertSame(2, $summary['total_units']);
        $this->assertSame(80.0, $summary['total_amount']);
        $this->assertSame(100.0, $summary['gross_amount']);
        $this->assertSame(20.0, $summary['discount_amount']);
        $this->assertSame(20.0, $summary['total_cost']);
        $this->assertSame(60.0, $summary['profit_amount']);
    }

    public function test_orders_report_applies_date_range_to_rows_and_financial_summary(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create();
        $product = Product::factory()->create(['cost_rm' => 10]);

        $includedOrder = $this->createOrder($agent, $product, 'A3D-IN-RANGE', '2026-08-10 09:00:00', 2, 50, 5);
        $this->createOrder($agent, $product, 'A3D-OUT-RANGE', '2026-08-12 09:00:00', 1, 25, 5);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.orders.index', [
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
        ]));

        $summary = $response->viewData('summary');

        $response->assertOk()
            ->assertSeeText($includedOrder->order_number)
            ->assertDontSeeText('A3D-OUT-RANGE')
            ->assertSeeText('Advanced order summary')
            ->assertSeeText('Gross profit')
            ->assertSeeText('Net profit');
        $this->assertSame(1, $summary['total']);
        $this->assertSame(1, $summary['completed']);
        $this->assertSame(2, $summary['total_units']);
        $this->assertSame(55.0, $summary['sales_amount']);
        $this->assertSame(20.0, $summary['cost_amount']);
        $this->assertSame(30.0, $summary['gross_profit']);
        $this->assertSame(30.0, $summary['total_profit']);
    }

    public function test_report_date_range_must_be_complete_and_ordered(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.sales.index'))
            ->get(route('admin.sales.index', ['start_date' => '2026-08-11']))
            ->assertRedirect(route('admin.sales.index'))
            ->assertSessionHasErrors('end_date');

        $this->from(route('admin.orders.index'))
            ->get(route('admin.orders.index', [
                'start_date' => '2026-08-11',
                'end_date' => '2026-08-10',
            ]))
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHasErrors('end_date');
    }

    /** @return array{Agent, BusinessSite, BusinessSiteOperation, PosSession} */
    private function posContext(): array
    {
        $agent = Agent::factory()->create();
        $site = BusinessSite::query()->create(['site_name' => 'Report Site', 'city' => 'Klang']);
        $operation = BusinessSiteOperation::query()->create([
            'business_site_id' => $site->getKey(),
            'opened_at' => '2026-08-01 00:00:00',
            'closed_at' => '2026-08-31 23:59:59',
        ]);
        $session = PosSession::query()->create([
            'agent_id' => $agent->getKey(),
            'business_site_id' => $site->getKey(),
            'signed_in_at' => '2026-08-01 08:00:00',
            'signed_out_at' => '2026-08-31 18:00:00',
        ]);

        return [$agent, $site, $operation, $session];
    }

    private function createSale(
        Agent $agent,
        BusinessSite $site,
        BusinessSiteOperation $operation,
        PosSession $session,
        Product $product,
        string $number,
        string $soldAt,
        float $amount,
        int $quantity,
    ): PosSale {
        $sale = PosSale::query()->create([
            'sale_number' => $number,
            'pos_session_id' => $session->getKey(),
            'business_site_id' => $site->getKey(),
            'business_site_operation_id' => $operation->getKey(),
            'recorded_by_agent_id' => $agent->getKey(),
            'sales_agent_id' => $agent->getKey(),
            'payment_method' => PosSale::PaymentCash,
            'total_amount' => $amount,
            'sold_at' => $soldAt,
        ]);

        PosSaleItem::query()->create([
            'pos_sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'product_code' => $product->prd_code,
            'product_name' => $product->prd_name,
            'quantity' => $quantity,
            'unit_price' => 50,
            'agent_discount_percentage' => 10,
            'agent_discount_amount' => 10,
            'customer_discount_amount' => 10,
            'line_total' => $amount,
        ]);

        return $sale;
    }

    private function createOrder(
        Agent $agent,
        Product $product,
        string $number,
        string $placedAt,
        int $quantity,
        float $subtotal,
        float $deliveryFee,
    ): Order {
        $order = Order::query()->create([
            'idempotency_key' => (string) str()->uuid(),
            'order_number' => $number,
            'agent_id' => $agent->getKey(),
            'status' => Order::StatusCompleted,
            'fulfilment_method' => 'delivery',
            'recipient_name' => 'Report Recipient',
            'phone_number' => '0123456789',
            'delivery_address' => 'Klang',
            'payment_method' => 'bank_transfer',
            'payment_status' => Order::PaymentStatusPaid,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $subtotal + $deliveryFee,
            'total_units' => $quantity,
            'placed_at' => $placedAt,
            'completed_at' => $placedAt,
        ]);

        $order->items()->create([
            'product_id' => $product->getKey(),
            'product_code' => $product->prd_code,
            'product_name' => $product->prd_name,
            'quantity' => $quantity,
            'reserved_quantity' => $quantity,
            'unit_selling_price' => $subtotal / $quantity,
            'discount_percentage' => 0,
            'unit_price' => $subtotal / $quantity,
            'line_total' => $subtotal,
            'is_preorder' => false,
        ]);

        return $order;
    }
}
