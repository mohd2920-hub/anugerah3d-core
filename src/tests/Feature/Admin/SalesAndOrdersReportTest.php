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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesAndOrdersReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sales_report_applies_date_range_to_rows_and_financial_summary(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
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

        $allSales = $this->get(route('admin.sales.index', ['period' => 'all']));
        $allSales->assertOk()->assertSeeText('Keseluruhan')->assertDontSeeText('POS-IN-RANGE')->assertDontSeeText('POS-OUT-RANGE');
        $this->get(route('admin.sales.transactions', $allSales->viewData('filterQuery')))->assertOk()->assertSeeText('POS-IN-RANGE')->assertSeeText('POS-OUT-RANGE');
        $this->assertSame(2, $allSales->viewData('summary')['transaction_count']);
        $this->assertSame(130.0, $allSales->viewData('summary')['total_amount']);
        $this->assertSame(3, $allSales->viewData('summary')['total_units']);
        $this->assertSame(2, $allSales->viewData('summary')['discounted_units']);
        $this->get(route('admin.sales.index', ['single_date' => '2026-08-11']))->assertViewHas('summary', fn (array $summary): bool => $summary['discounted_units'] === 0);
        $searchedSales = $this->get(route('admin.sales.index', ['period' => 'all', 'search' => 'POS-IN-RANGE']));
        $searchedSales->assertOk()->assertDontSeeText('POS-OUT-RANGE');
        $this->assertSame(80.0, $searchedSales->viewData('summary')['total_amount']);
        $customSales = $this->get(route('admin.sales.index', ['period' => 'all', 'single_date' => '2026-08-10']));
        $customSales->assertOk()->assertDontSeeText('POS-OUT-RANGE');
        $this->assertSame(1, $customSales->viewData('summary')['transaction_count']);

        $singleDay = $this->get(route('admin.sales.index', ['single_date' => '2026-08-10']));
        $singleDay->assertOk()->assertSeeText('Tarikh Tertentu')->assertSeeText('Lihat Senarai Jualan')->assertDontSeeText('POS-IN-RANGE');
        $list = $this->get(route('admin.sales.transactions', $singleDay->viewData('filterQuery')));
        $list->assertOk()->assertSeeText('POS-IN-RANGE')->assertDontSeeText('POS-OUT-RANGE')->assertSeeText('Kembali ke Ringkasan Sales');
        $this->assertSame(1, $list->viewData('sales')->total());
        $this->assertSame(80.0, $singleDay->viewData('summary')['total_amount']);
        $this->assertSame(1, $singleDay->viewData('summary')['transaction_count']);
        $this->get(route('admin.sales.index', ['single_date' => '2026-02-30']))->assertSessionHasErrors('single_date');

        $summary = $response->viewData('summary');

        $response->assertOk()
            ->assertDontSeeText($includedSale->sale_number)
            ->assertDontSeeText('POS-OUT-RANGE')
            ->assertSeeText('Advanced sales summary')
            ->assertSeeText('Start date')
            ->assertSeeText('End date');
        $this->assertSame(1, $summary['transaction_count']);
        $this->assertSame(2, $summary['total_units']);
        $this->assertSame(80.0, $summary['total_amount']);
        $this->assertSame(100.0, $summary['gross_amount']);
        $this->assertSame(20.0, $summary['discount_amount']);
        $this->assertSame(2, $summary['discounted_units']);
        $response->assertSeeText('2 unit produk terjual')->assertSeeText('2 unit produk diskaun');
        $this->assertArrayNotHasKey('agent_discount_amount', $summary);
        $this->assertSame(20.0, $summary['customer_discount_amount']);
        $this->assertSame(20.0, $summary['total_cost']);
        $this->assertSame(60.0, $summary['profit_amount']);
        $this->assertNull($response->viewData('discountDetails'));

        $breakdownResponse = $this->get(route('admin.sales.index', [
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'show_discounts' => 1,
        ]));
        $discountDetails = $breakdownResponse->viewData('discountDetails');

        $breakdownResponse->assertOk()
            ->assertSeeText('Discount breakdown by transaction')
            ->assertDontSeeText('Agent discount')
            ->assertSeeText('Customer discount')
            ->assertSeeText($includedSale->sale_number)
            ->assertDontSeeText('POS-OUT-RANGE');
        $this->assertSame(1, $discountDetails->total());
        $this->assertSame($includedSale->getKey(), $discountDetails->first()->pos_sale_id);
    }

    public function test_sales_return_clears_search_filters_but_preserves_period_and_dates(): void
    {
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        [, $site] = $this->posContext();
        foreach ([['period' => 'all'], ['period' => 'yesterday'], ['single_date' => '2026-08-10'], ['start_date' => '2026-08-01', 'end_date' => '2026-08-10']] as $dates) {
            $list = $this->get(route('admin.sales.transactions', $dates + ['business_site_id' => $site->id, 'search' => 'POS-SEARCH', 'payment_method' => PosSale::PaymentCash]));
            $list->assertOk();
            $this->assertSame(3, $list->viewData('activeFilterCount'));
            $returnQuery = $list->viewData('summaryReturnQuery');
            $this->assertArrayNotHasKey('business_site_id', $returnQuery);
            $this->assertArrayNotHasKey('search', $returnQuery);
            $this->assertArrayNotHasKey('payment_method', $returnQuery);
            $list->assertSee(e(route('admin.sales.index', $returnQuery)), false);
            $summary = $this->get(route('admin.sales.index', $returnQuery));
            $summary->assertOk();
            $this->assertSame(0, $summary->viewData('activeFilterCount'));
            $this->assertSame($list->viewData('periodLabel'), $summary->viewData('periodLabel'));
            $this->assertSame(0, $summary->viewData('filters')['business_site_id']);
            $this->assertSame('', $summary->viewData('filters')['search']);
            $this->assertSame('', $summary->viewData('filters')['payment_method']);
        }
    }

    public function test_orders_report_applies_date_range_to_rows_and_financial_summary(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
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

    public function test_orders_count_below_cost_products_and_units_within_filters(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $agent = Agent::factory()->create();
        $product = Product::factory()->create(['cost_rm' => 10]);
        $this->createOrder($agent, $product, 'LOSS-A', '2026-08-10 09:00:00', 2, 12, 20);
        $this->createOrder($agent, $product, 'LOSS-B', '2026-08-10 10:00:00', 3, 15, 0);
        $this->createOrder($agent, $product, 'EQUAL-COST', '2026-08-10 11:00:00', 4, 40, 0);
        $this->createOrder($agent, $product, 'OUTSIDE', '2026-08-11 09:00:00', 8, 8, 0);
        $cancelled = $this->createOrder($agent, $product, 'CANCELLED', '2026-08-10 12:00:00', 7, 7, 0);
        $cancelled->update(['status' => Order::StatusCancelled]);
        $unknown = Product::factory()->create(['cost_rm' => null]);
        $this->createOrder($agent, $unknown, 'UNKNOWN-COST', '2026-08-10 13:00:00', 9, 0, 0);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.orders.index', [
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
        ]))->assertOk()->assertSeeText('1 produk · 5 unit');
        $this->assertSame(1, $response->viewData('summary')['below_cost_products']);
        $this->assertSame(5, $response->viewData('summary')['below_cost_units']);
        $this->get(route('admin.orders.index', ['start_date' => '2026-08-12', 'end_date' => '2026-08-12']))
            ->assertOk()->assertSeeText('0 produk · 0 unit');
    }

    public function test_agent_order_rankings_use_completed_orders_and_top_product_units(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $bestProduct = Product::factory()->create(['prd_name' => 'Top Clicker']);
        $agents = Agent::factory()->count(6)->create();
        foreach ($agents as $index => $agent) {
            $this->createOrder($agent, $product, 'RANK-'.$index, '2026-08-10 09:00:00', 1, $index === 0 ? 200 : 100 - $index, 30);
        }
        $winner = $agents->last();
        $this->createOrder($winner, $bestProduct, 'WINNER', '2026-08-10 10:00:00', 5, 10, 50);
        $cancelled = $this->createOrder($agents->first(), $product, 'IGNORED-RANK', '2026-08-10 11:00:00', 100, 9999, 0);
        $cancelled->update(['status' => Order::StatusCancelled]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.agents.index'))->assertOk();
        $rankings = $response->viewData('topOrderingAgents');
        $this->assertCount(5, $rankings);
        $this->assertSame($agents->first()->id, $rankings->first()->id);
        $this->assertEquals(200, $rankings->first()->order_sales);
        $this->assertEquals(105, $rankings[1]->order_sales);
        $this->assertEquals(6, $rankings[1]->order_units);
        $this->assertSame('Top Clicker', $rankings[1]->top_order_product->product_name);
        $this->assertSame($winner->id, $rankings[1]->id);
        $response->assertSeeText('Top 5 Ejen Aktif Order')->assertSeeText('Top Clicker');
        $winner->update(['referrer_id' => $agents->first()->id]);
        $this->createOrder($winner, $product, 'SEPTEMBER', '2026-09-30 23:59:59', 3, 20, 0);
        $month = $this->get(route('admin.agents.index', ['ranking_period' => 'month', 'ranking_month' => '2026-09']))
            ->assertOk()->assertSeeText('Introducer:')->assertSeeText($agents->first()->agt_name);
        $this->assertCount(1, $month->viewData('topOrderingAgents'));
        $this->assertEquals(20, $month->viewData('topOrderingAgents')->first()->order_sales);
        $this->assertEquals(3, $month->viewData('topOrderingAgents')->first()->order_units);
        $this->assertSame($product->id, $month->viewData('topOrderingAgents')->first()->top_order_product->product_id);
        $range = $this->get(route('admin.agents.index', ['ranking_period' => 'custom', 'ranking_start' => '2026-09-30', 'ranking_end' => '2026-09-30']))->assertOk();
        $this->assertCount(1, $range->viewData('topOrderingAgents'));
        $this->get(route('admin.agents.index', ['ranking_period' => 'month', 'ranking_month' => '2026-10']))
            ->assertOk()->assertViewHas('topOrderingAgents', fn ($agents): bool => $agents->isEmpty());
        $this->get(route('admin.agents.index', ['ranking_period' => 'custom', 'ranking_start' => '2026-09-30', 'ranking_end' => '2026-09-01']))->assertSessionHasErrors('ranking_end');
        $this->get(route('admin.agents.index', ['ranking_period' => 'month']))->assertSessionHasErrors('ranking_month');

    }

    public function test_report_date_range_must_be_complete_and_ordered(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();

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
    public function test_sales_rankings_limit_and_order_products_and_agents_with_active_filters(): void
    {
        [$agent, $site, $operation, $session] = $this->posContext();
        $agents = collect([$agent])->concat(Agent::factory()->count(3)->create());
        $agents[3]->update(['profile_picture' => 'images/ranking-agent.jpg']);
        $products = Product::factory()->count(11)->create();
        foreach ($products as $index => $product) {
            $this->createSale($agents[$index % 4], $site, $operation, $session, $product, 'RANK-'.$index, '2026-08-10 12:00:00', ($index + 1) * 10, $index + 1);
        }
        $this->createSale($agents[3], $site, $operation, $session, $products[0], 'RANK-TIE', '2026-08-10 13:00:00', 120, 10);
        $voided = $this->createSale($agent, $site, $operation, $session, $products[1], 'RANK-VOID', '2026-08-10 14:00:00', 9999, 500);
        $voided->update(['voided_at' => now()]);
        $this->createSale($agent, $site, $operation, $session, $products[1], 'RANK-OTHER-DAY', '2026-08-11 12:00:00', 8888, 400);
        $response = $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin')->get(route('admin.sales.index', ['single_date' => '2026-08-10']));
        $response->assertOk()->assertSeeText('Top 10 Produk Paling Laris')->assertSeeText('Top 3 Ejen')->assertSee('images/ranking-agent.jpg', false);
        $rankings = $response->viewData('summary');
        $this->assertCount(10, $rankings['top_products']);
        $this->assertSame($products[0]->id, $rankings['top_products'][0]->product_id);
        $this->assertSame($products[10]->id, $rankings['top_products'][1]->product_id);
        $this->assertCount(3, $rankings['top_agents']);
        $this->assertSame($agents[3]->id, $rankings['top_agents'][0]->sales_agent_id);
        $this->assertSame(240.0, (float) $rankings['top_agents'][0]->total_amount);
        $empty = $this->get(route('admin.sales.index', ['single_date' => '2026-08-12']));
        $empty->assertOk()->assertSeeText('No product sales for this period.')->assertSeeText('No agent sales for this period.');
        $this->assertCount(0, $empty->viewData('summary')['top_products']);
        $this->assertCount(0, $empty->viewData('summary')['top_agents']);
    }

    public function test_sales_days_count_unique_session_dates_and_exclude_voided_sales(): void
    {
        [$agent, $site, $operation, $session] = $this->posContext();
        $otherSite = BusinessSite::query()->create(['site_name' => 'Other Day Site', 'city' => 'Klang']);
        $product = Product::factory()->create();
        $this->createSale($agent, $site, $operation, $session, $product, 'DAY-ONE', '2026-08-10 00:00:00', 10, 1);
        $this->createSale($agent, $site, $operation, $session, $product, 'DAY-ONE-LATE', '2026-08-10 23:59:59', 10, 1);
        $this->createSale($agent, $otherSite, $operation, $session, $product, 'DAY-ONE-OTHER', '2026-08-10 12:00:00', 10, 1);
        $this->createSale($agent, $otherSite, $operation, $session, $product, 'DAY-TWO', '2026-08-11 00:00:00', 10, 1);
        $voided = $this->createSale($agent, $site, $operation, $session, $product, 'DAY-VOID', '2026-08-12 12:00:00', 10, 1);
        $voided->update(['voided_at' => now()]);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $all = $this->get(route('admin.sales.index', ['period' => 'all']));
        $all->assertOk()->assertSeeText('Hari Jualan')->assertSeeText('2 hari')->assertSeeText('Lihat Senarai Jualan');
        $this->assertSame(2, $all->viewData('summary')['sales_days']);
        $siteOnly = $this->get(route('admin.sales.index', ['period' => 'all', 'business_site_id' => $site->id]));
        $siteOnly->assertOk();
        $this->assertSame(1, $siteOnly->viewData('summary')['sales_days']);
        $single = $this->get(route('admin.sales.index', ['single_date' => '2026-08-10']));
        $single->assertOk();
        $this->assertSame(1, $single->viewData('summary')['sales_days']);
        $empty = $this->get(route('admin.sales.index', ['single_date' => '2026-08-12']));
        $empty->assertOk();
        $this->assertSame(0, $empty->viewData('summary')['sales_days']);
    }

    public function test_overnight_sales_follow_session_date_across_months_in_all_report_sections(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 1)->setTime(12, 0));
        [$agent, $site, $operation, $session] = $this->posContext();
        $operation->update(['opened_at' => '2026-08-31 19:00:00', 'closed_at' => '2026-09-01 05:00:00']);
        $product = Product::factory()->create(['cost_rm' => 10]);
        $sales = collect();
        foreach (['2026-08-31 23:00:00', '2026-09-01 04:00:00'] as $index => $time) {
            $sale = $this->createSale($agent, $site, $operation, $session, $product, 'OVERNIGHT-'.$index, $time, 40, 1);
            $sale->update(['business_site_operation_id' => $operation->id]);
            $sales->push($sale);
        }
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        foreach ([['single_date' => '2026-08-31'], ['period' => 'yesterday'], ['start_date' => '2026-08-01', 'end_date' => '2026-08-31'], ['period' => 'all', 'business_site_id' => $site->id, 'payment_method' => 'cash']] as $filter) {
            $response = $this->get(route('admin.sales.index', $filter + ['show_discounts' => 1]))->assertOk();
            $summary = $response->viewData('summary');
            $this->assertSame(2, $summary['transaction_count']);
            $this->assertSame(1, $summary['sales_days']);
            $this->assertSame(80.0, $summary['total_amount']);
            $this->assertSame(20.0, $summary['discount_amount']);
            $this->assertSame(20.0, $summary['total_cost']);
            $this->assertEquals(80, $summary['by_site']->sole()->total_amount);
            $this->assertEquals(2, $summary['top_products']->sole()->total_quantity);
            $this->assertEquals(80, $summary['top_agents']->sole()->total_amount);
            $this->assertSame(2, $response->viewData('discountDetails')->total());
            $this->get(route('admin.sales.transactions', $filter))->assertOk()->assertSeeText('OVERNIGHT-0')->assertSeeText('OVERNIGHT-1');
        }
        foreach ([['single_date' => '2026-09-01'], ['period' => 'today'], ['period' => 'month']] as $filter) {
            $this->get(route('admin.sales.index', $filter))->assertOk()
                ->assertViewHas('summary', fn (array $summary): bool => $summary['transaction_count'] === 0 && $summary['sales_days'] === 0);
        }
        $this->assertSame('2026-09-01 04:00:00', $sales->last()->fresh()->sold_at->format('Y-m-d H:i:s'));
        $sales->last()->update(['voided_at' => now()]);
        $this->get(route('admin.sales.index', ['single_date' => '2026-08-31']))->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['total_amount'] === 40.0 && $summary['sales_days'] === 1);
    }

    public function test_sale_report_date_does_not_affect_new_sales_in_the_same_session(): void
    {
        [$agent, $site, $operation, $session] = $this->posContext();
        $operation->update(['opened_at' => '2026-09-13 04:41:31', 'closed_at' => '2026-09-16 01:01:17']);
        $product = Product::factory()->create(['prd_balance' => 100]);
        $sales = collect();
        foreach (['2026-09-13 19:53:04', '2026-09-15 23:23:27'] as $index => $time) {
            $sale = $this->createSale($agent, $site, $operation, $session, $product, 'REPORT-DATE-'.$index, $time, 40, 1);
            $sale->update(['business_site_operation_id' => $operation->id]);
            $sales->push($sale);
        }
        $before = $sales->map(fn (PosSale $sale): array => collect($sale->fresh()->getAttributes())->except(['report_date', 'updated_at'])->all())->all();
        foreach ($sales as $sale) {
            $sale->forceFill(['report_date' => '2026-09-12'])->save();
        }
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        foreach ([['single_date' => '2026-09-12'], ['start_date' => '2026-09-12', 'end_date' => '2026-09-12'], ['period' => 'all']] as $filters) {
            $response = $this->get(route('admin.sales.index', $filters + ['show_discounts' => 1]))->assertOk();
            $summary = $response->viewData('summary');
            $this->assertSame(2, $summary['transaction_count']);
            $this->assertSame(1, $summary['sales_days']);
            $this->assertSame(80.0, $summary['total_amount']);
            $this->assertEquals(80, $summary['by_site']->sole()->total_amount);
            $this->assertEquals(2, $summary['top_products']->sole()->total_quantity);
            $this->assertSame(2, $response->viewData('discountDetails')->total());
            $this->get(route('admin.sales.transactions', $filters))->assertOk()->assertSeeText('REPORT-DATE-0')->assertSeeText('REPORT-DATE-1');
        }
        foreach (['2026-09-13', '2026-09-15'] as $date) {
            $this->get(route('admin.sales.index', ['single_date' => $date]))->assertOk()
                ->assertViewHas('summary', fn (array $summary): bool => $summary['transaction_count'] === 0);
        }
        $new = $this->createSale($agent, $site, $operation, $session, $product, 'LATER-SALE', '2026-09-13 04:41:31', 5, 1);
        $this->assertNull($new->fresh()->report_date);
        $this->get(route('admin.sales.transactions', ['single_date' => '2026-09-13']))->assertOk()->assertSeeText('LATER-SALE')->assertDontSeeText('REPORT-DATE-0');
        $this->get(route('admin.sales.index', ['single_date' => '2026-09-12']))->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['transaction_count'] === 2 && $summary['total_amount'] === 80.0);
        $this->get(route('admin.sales.index', ['single_date' => '2026-09-13']))->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['transaction_count'] === 1 && $summary['total_amount'] === 5.0);
        $this->get(route('admin.sales.index', ['period' => 'all']))->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['sales_days'] === 2 && $summary['total_amount'] === 85.0);
        $this->assertSame($before, $sales->map(fn (PosSale $sale): array => collect($sale->fresh()->getAttributes())->except(['report_date', 'updated_at'])->all())->all());
        $this->assertSame(100, $product->fresh()->prd_balance);
        $this->assertSame('2026-09-13 04:41:31', $operation->fresh()->opened_at->format('Y-m-d H:i:s'));
    }

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
        if ($operation->business_site_id !== $site->id || $operation->opened_at->toDateString() !== Carbon::parse($soldAt)->toDateString()) {
            $operation = BusinessSiteOperation::query()->firstOrCreate([
                'business_site_id' => $site->id,
                'opened_at' => Carbon::parse($soldAt)->startOfDay(),
            ], ['closed_at' => Carbon::parse($soldAt)->endOfDay()]);
        }
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
            'customer_discount_amount' => 50 * $quantity - $amount,
            'line_total' => $amount,
        ]);

        return $sale;
    }

    public function test_order_costs_use_character_pricing_in_summary_rows_and_details(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $agent = Agent::factory()->create(['referrer_id' => null]);
        $product = Product::factory()->create(['product_type' => 'clicker', 'cost_rm' => 999]);
        DB::table('product_clicker_prices')->insert([
            ['product_id' => $product->id, 'character_count' => 3, 'price_rm' => 9, 'cost_rm' => 3.3],
            ['product_id' => $product->id, 'character_count' => 6, 'price_rm' => 15, 'cost_rm' => 6.6],
        ]);
        $order = $this->createOrder($agent, $product, 'CLICKER-COST', '2026-09-09 12:00:00', 5, 45, 0);
        $order->items()->update(['clicker_character_count' => 3]);
        $response = $this->actingAs($admin, 'admin')->get(route('admin.orders.index'))->assertOk();
        $this->assertSame(16.5, $response->viewData('summary')['cost_amount']);
        $this->assertSame(28.5, $response->viewData('summary')['total_profit']);
        $this->assertSame(16.5, $response->viewData('orders')->first()->total_cost);
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSeeText('Cost: RM 16.50');
        $order->items()->update(['clicker_character_count' => 6, 'quantity' => 2]);
        $response = $this->get(route('admin.orders.index'))->assertOk();
        $this->assertSame(13.2, $response->viewData('summary')['cost_amount']);
        $order->items()->update(['clicker_character_count' => null]);
        $response = $this->get(route('admin.orders.index'))->assertOk();
        $this->assertTrue($response->viewData('summary')['cost_incomplete']);
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSeeText('Maklumat varian belum lengkap');
    }

    public function test_sales_costs_distinguish_fixed_products_and_configurable_clickers(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        [$agent, $site, $operation, $session] = $this->posContext();
        $product = Product::factory()->create(['product_type' => 'standard', 'cost_rm' => 5.64]);
        $sale = $this->createSale($agent, $site, $operation, $session, $product, 'FIXED-COST', '2026-09-05 12:00:00', 11, 1);
        $this->actingAs($admin, 'admin')->get(route('admin.sales.show', $sale))->assertOk()->assertSeeText('RM 5.64');
        $product->update(['product_type' => 'clicker']);
        $response = $this->get(route('admin.sales.show', $sale))->assertOk()->assertSeeText('Maklumat varian belum lengkap');
        $this->assertTrue($response->viewData('itemSummary')['cost_incomplete']);
        DB::table('product_clicker_prices')->insert(['product_id' => $product->id, 'character_count' => 4, 'cost_rm' => 4.4, 'price_rm' => 11]);
        $sale->items()->update(['clicker_configuration' => ['character_count' => 4, 'characters' => ['A', 'B', 'C', 'D'], 'casing_name' => 'Pink', 'huruf_name' => 'Gold']]);
        $response = $this->get(route('admin.sales.show', $sale))->assertOk()->assertSeeText('RM 4.40');
        $this->assertFalse($response->viewData('itemSummary')['cost_incomplete']);
        $response = $this->get(route('admin.sales.index', ['start_date' => '2026-09-05', 'end_date' => '2026-09-05']))->assertOk();
        $this->assertSame(4.4, $response->viewData('summary')['total_cost']);
        $this->assertSame(0, $response->viewData('summary')['missing_units']);
        $sale->items()->update(['unit_cost' => 3]);
        $response = $this->get(route('admin.sales.show', $sale))->assertOk();
        $this->assertSame(3.0, $response->viewData('itemSummary')['capital_total']);
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
