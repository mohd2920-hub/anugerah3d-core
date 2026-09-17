<?php

namespace Tests\Feature\Admin;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\Product;
use App\Support\DashboardInventory;
use App\Support\DashboardReport;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 10)->setTime(12, 0));
        $this->assertStringEndsWith('_test', config('database.connections.'.config('database.default').'.database'));
    }

    public function test_paid_sales_cost_snapshots_voids_and_refunds_are_read_only_and_reconcile(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $site = BusinessSite::create(['site_name' => 'Dashboard Site', 'city' => 'Klang']);
        $product = Product::factory()->create(['cost_rm' => 10, 'price_selling' => 50, 'prd_balance' => 25]);
        $this->sale($product, $site, '2026-09-02 10:00:00', 80, 7);
        $this->sale($product, $site, '2026-09-03 10:00:00', 900, 7, true);
        $this->order($product, 'paid', 'completed', 100);
        $this->order($product, 'unpaid', 'pending', 400);
        $this->order($product, 'paid', 'cancelled', 500);
        $customer = $this->order($product, 'paid', 'completed', 100, true);
        DB::table('customer_orders')->where('id', $customer)->update(['refunded_product_amount' => 20, 'commission_rate' => 25]);
        DB::table('salary_payments')->insert(['submission_token' => (string) str()->uuid(), 'recipient_key' => 'agent:1', 'recipient_name' => 'Test', 'work_date' => '2026-09-02', 'paid_date' => '2026-09-05', 'site_name' => $site->site_name, 'duplicate_key' => hash('sha256', 'test'), 'amount_cents' => 1500, 'reason' => 'test', 'created_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $before = DB::table('products')->where('id', $product->id)->first();
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['year' => 2026, 'month' => 9]));
        $response->assertOk()->assertJsonPath('summary.sales', 260)->assertJsonPath('summary.capital', 54)->assertJsonPath('summary.commission', 20)->assertJsonPath('summary.salary', 15)->assertJsonPath('summary.cost', 89)->assertJsonPath('summary.profit', 171)->assertJsonPath('summary.transactions', 3);
        $response->assertJsonPath('series.1.pos_sales', 80)->assertJsonPath('series.1.order_sales', 100)->assertJsonPath('series.1.customer_sales', 80);
        $this->assertEquals(80, array_sum(array_column($response->json('series'), 'pos_sales')));
        $this->assertEquals(100, array_sum(array_column($response->json('series'), 'order_sales')));
        $this->assertEquals(80, array_sum(array_column($response->json('series'), 'customer_sales')));
        $this->assertEquals(260, array_sum(array_column($response->json('series'), 'sales')));
        $this->assertEquals(89, array_sum(array_column($response->json('series'), 'cost')));
        $this->assertEquals($before, DB::table('products')->where('id', $product->id)->first());
        foreach ($queries as $sql) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|drop|truncate)\b/i', $sql);
        }
        $this->assertStringNotContainsString('phone_number', $response->getContent());
    }

    public function test_partial_month_comparison_clamps_to_equivalent_day_and_future_points_are_marked(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $site = BusinessSite::create(['site_name' => 'Site', 'city' => 'Klang']);
        $this->sale($product, $site, '2026-08-05 10:00:00', 100, 5);
        $this->sale($product, $site, '2026-08-20 10:00:00', 500, 5);
        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['year' => 2026, 'month' => 9]));
        $response->assertOk()->assertJsonPath('previous.sales', 100)->assertJsonPath('series.10.future', true);
        $this->assertStringStartsWith('2026-08-10', $response->json('period.previous_end'));
    }

    public function test_site_filter_excludes_online_orders_and_other_site_salary(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $site = BusinessSite::create(['site_name' => 'Site', 'city' => 'Klang']);
        $other = BusinessSite::create(['site_name' => 'Other', 'city' => 'Klang']);
        $this->sale($product, $site, '2026-09-01', 50, 5);
        $this->sale($product, $other, '2026-09-01', 150, 5);
        $this->order($product, 'paid', 'completed', 100);
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['site' => $site->id]))->assertOk()->assertJsonPath('summary.sales', 50);
    }

    public function test_dashboard_only_staff_do_not_receive_financial_or_inventory_records(): void
    {
        $staff = AdminUser::factory()->create();
        $role = AdminRole::factory()->create(['permissions' => ['dashboard.view']]);
        $staff->accessRoles()->attach($role);
        $product = Product::factory()->create();
        $this->order($product, 'paid', 'completed', 100);
        $this->actingAs($staff, 'admin')->getJson(route('admin.dashboard.data'))->assertOk()->assertJsonPath('summary.sales', 0)->assertJsonPath('transactions.total', 0);
        $this->getJson(route('admin.dashboard.data', ['channel' => 'orders']))->assertForbidden();
        $this->getJson(route('admin.dashboard.inventory'))->assertForbidden();
        $this->get(route('admin.dashboard.export', ['channel' => 'orders']))->assertForbidden();
        $this->get(route('admin.dashboard.export'))->assertOk()->assertStreamedContent("\xEF\xBB\xBFRujukan,Tarikh,Saluran,Jenis,Lokasi,\"Jualan RM\",\"Kos direkodkan / anggaran RM\",\"Anggaran untung RM\"\n");
    }

    public function test_staff_without_dashboard_access_and_guests_cannot_access_endpoints(): void
    {
        $this->getJson(route('admin.dashboard.data'))->assertUnauthorized();
        $staff = AdminUser::factory()->create();
        $this->actingAs($staff, 'admin')->getJson(route('admin.dashboard.data'))->assertForbidden();
        $this->get(route('admin.dashboard.export'))->assertForbidden();
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['month' => 2, 'day' => 30]))->assertUnprocessable()->assertJsonValidationErrors('day');
        $this->getJson(route('admin.dashboard.data', ['month' => 12]))->assertUnprocessable()->assertJsonValidationErrors('month');
        $this->getJson(route('admin.dashboard.data', ['channel' => 'bad', 'year' => 99999]))->assertUnprocessable();
    }

    public function test_inventory_does_not_double_subtract_reservations_or_count_casing_balance_twice(): void
    {
        $product = Product::factory()->create(['product_type' => 'normal', 'prd_balance' => 10, 'cost_rm' => 5, 'price_selling' => 12]);
        $this->order($product, 'unpaid', 'processing', 24);
        $clicker = Product::factory()->create(['product_type' => 'clicker', 'prd_balance' => 999, 'casing_stock_enabled' => true]);
        $casing = DB::table('product_clicker_images')->insertGetId(['product_id' => $clicker->id, 'image_type' => 'casing', 'image_path' => 'test.jpg', 'position' => 1]);
        DB::table('product_clicker_prices')->insert(['product_id' => $clicker->id, 'character_count' => 3, 'price_rm' => 20, 'cost_rm' => 8]);
        DB::table('product_clicker_stocks')->insert(['casing_image_id' => $casing, 'character_count' => 3, 'quantity' => 4]);
        $data = app(DashboardInventory::class)->data([]);
        $this->assertSame(14, $data['summary']['units']);
        $this->assertSame(2, $data['summary']['reserved']);
        $this->assertSame(82.0, $data['summary']['asset']);
        $this->assertSame(118.0, $data['summary']['potential_profit']);
        $this->assertSame(10, $product->fresh()->prd_balance);
        $this->assertSame(1, app(DashboardInventory::class)->data(['stock_search' => $product->prd_code])['summary']['records']);
    }

    public function test_missing_inventory_cost_is_unknown_and_empty_reports_do_not_divide_by_zero(): void
    {
        Product::factory()->create(['product_type' => 'clicker', 'casing_stock_enabled' => false, 'prd_balance' => 10]);
        $inventory = app(DashboardInventory::class)->data([]);
        $this->assertSame(1, $inventory['summary']['unknown']);
        $this->assertNull($inventory['rows'][0]['potential_profit']);
        $admin = AdminUser::factory()->superAdmin()->create();
        $data = app(DashboardReport::class)->data($admin, []);
        $this->assertNull($data['summary']['margin']);
    }

    public function test_inventory_excludes_discontinued_products_unless_requested(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        Product::factory()->create(['prd_balance' => 0, 'cost_rm' => 2, 'price_selling' => 5]);
        Product::factory()->create(['prd_balance' => 5, 'cost_rm' => 2, 'price_selling' => 5, 'is_visible_to_agents' => false]);
        $discontinued = Product::factory()->create(['prd_balance' => 3, 'cost_rm' => 4, 'price_selling' => 10]);
        $discontinued->forceFill(['discontinued_at' => now()])->save();

        $url = route('admin.dashboard.inventory');
        $this->actingAs($admin, 'admin')->getJson($url)->assertOk()
            ->assertJsonPath('summary.records', 2)->assertJsonPath('summary.units', 5)
            ->assertJsonPath('summary.asset', 10)->assertJsonPath('summary.potential_sales', 25)
            ->assertJsonPath('summary.potential_profit', 15)->assertJsonPath('summary.empty', 1)
            ->assertJsonPath('rows.0.is_discontinued', false);
        $this->getJson($url.'?stock_include_discontinued=1')->assertOk()
            ->assertJsonPath('summary.records', 3)->assertJsonPath('summary.units', 8)
            ->assertJsonPath('summary.asset', 22)->assertJsonPath('summary.potential_sales', 55)
            ->assertJsonPath('summary.potential_profit', 33)->assertJsonPath('rows.1.is_discontinued', true);
        $this->getJson($url.'?stock_include_discontinued=0')->assertOk()->assertJsonPath('summary.records', 2);
        $this->getJson($url.'?stock_include_discontinued=invalid')->assertUnprocessable()
            ->assertJsonValidationErrors('stock_include_discontinued');
    }

    public function test_inventory_edit_links_require_edit_permission_and_target_the_casing(): void
    {
        $product = Product::factory()->create(['product_type' => 'normal', 'prd_balance' => 0]);
        $clicker = Product::factory()->create(['product_type' => 'clicker', 'casing_stock_enabled' => true]);
        $casing = DB::table('product_clicker_images')->insertGetId(['product_id' => $clicker->id, 'image_type' => 'casing', 'position' => 1, 'image_path' => 'test.jpg']);
        DB::table('product_clicker_stocks')->insert(['casing_image_id' => $casing, 'character_count' => 3, 'quantity' => 1]);
        $admin = AdminUser::factory()->superAdmin()->create();
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.inventory'))->assertOk()
            ->assertJsonPath('rows.0.edit_url', route('admin.products.edit', $product))
            ->assertJsonPath('rows.1.edit_url', route('admin.products.edit', $clicker).'#clicker-casing-'.$casing);
        $this->get(route('admin.dashboard'))->assertOk()->assertViewHas('inventory', fn (array $data): bool => $data['rows'][0]['edit_url'] === route('admin.products.edit', $product));

        $viewer = AdminUser::factory()->create();
        $viewer->accessRoles()->attach(AdminRole::factory()->create(['permissions' => ['dashboard.view', 'products.view']]));
        $this->actingAs($viewer, 'admin')->getJson(route('admin.dashboard.inventory'))->assertOk()
            ->assertJsonPath('rows.0.edit_url', null)->assertJsonPath('rows.1.edit_url', null);
        $this->get(route('admin.products.edit', $product))->assertForbidden();
    }

    public function test_bonus_is_counted_once_and_losses_are_not_clamped_to_zero(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        DB::table('weekly_closings')->insert(['week_key' => '2026-W36', 'period_start' => '2026-09-01', 'period_end' => '2026-09-07 23:59:59', 'status' => 'completed', 'total_payable_bonus' => 125, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['month' => 9]))
            ->assertOk()->assertJsonPath('summary.bonus', 125)->assertJsonPath('summary.cost', 125)->assertJsonPath('summary.profit', -125)->assertJsonPath('summary.transactions', 0);
    }

    public function test_deleted_pos_items_are_not_in_cost_and_export_neutralizes_spreadsheet_formulas(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create(['cost_rm' => 99]);
        $site = BusinessSite::create(['site_name' => '=COMMAND()', 'city' => 'Klang']);
        $sale = $this->sale($product, $site, '2026-09-02', 50, 3);
        DB::table('pos_sale_items')->insert(['pos_sale_id' => $sale, 'product_id' => $product->id, 'product_name' => 'Old item', 'product_code' => 'OLD', 'quantity' => 100, 'unit_price' => 50, 'unit_cost' => 99, 'line_total' => 5000, 'deleted_at' => now()]);
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['month' => 9, 'day' => 2]))
            ->assertOk()->assertJsonPath('summary.capital', 6)->assertJsonPath('summary.units', 2)->assertJsonPath('transactions.total', 1);
        $response = $this->get(route('admin.dashboard.export', ['month' => 9, 'day' => 2]))->assertOk();
        $this->assertStringContainsString("'=COMMAND()", $response->streamedContent());
    }

    public function test_clicker_order_cost_uses_matching_character_size_and_refunded_orders_are_excluded(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create(['product_type' => 'clicker', 'cost_rm' => 999]);
        DB::table('product_clicker_prices')->insert(['product_id' => $product->id, 'character_count' => 3, 'price_rm' => 50, 'cost_rm' => 8]);
        $order = $this->order($product, 'paid', 'completed', 100);
        DB::table('order_items')->where('order_id', $order)->update(['clicker_character_count' => 3]);
        $this->order($product, 'refunded', 'completed', 300, true);
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['month' => 9]))
            ->assertOk()->assertJsonPath('summary.sales', 100)->assertJsonPath('summary.capital', 16)->assertJsonPath('summary.estimated', 2);
    }

    public function test_cost_quality_details_filter_records_and_explain_item_cost_sources_without_writes(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $site = BusinessSite::create(['site_name' => 'Cost Site', 'city' => 'Klang']);
        $known = Product::factory()->create(['cost_rm' => 5]);
        $unknown = Product::factory()->create(['cost_rm' => null]);
        $snapshot = $this->sale($known, $site, '2026-09-02', 50, 3);
        $fallback = $this->sale($known, $site, '2026-09-02', 50, 3);
        $missing = $this->sale($unknown, $site, '2026-09-02', 50, 3);
        DB::table('pos_sale_items')->whereIn('pos_sale_id', [$fallback, $missing])->update(['unit_cost' => null]);
        $this->order($known, 'paid', 'completed', 60);
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['month' => 9, 'cost_quality' => 'all']));
        $response->assertOk()->assertJsonPath('transactions.total', 3);
        $this->assertSame(6, array_sum(array_column($response->json('transactions.rows'), 'estimated_units')));
        $response = $this->getJson(route('admin.dashboard.data', ['month' => 9, 'cost_quality' => 'missing']));
        $response->assertOk()->assertJsonPath('transactions.total', 1)->assertJsonPath('transactions.rows.0.missing_units', 2)
            ->assertJsonPath('transactions.rows.0.cost_items.0.source', 'missing')->assertJsonPath('transactions.rows.0.cost_items.0.unit_cost', null);
        $this->getJson(route('admin.dashboard.data', ['month' => 9, 'cost_quality' => 'estimated', 'channel' => 'orders']))
            ->assertOk()->assertJsonPath('transactions.total', 1)->assertJsonPath('transactions.rows.0.cost_items.0.source', 'current')->assertJsonPath('transactions.rows.0.cost_items.0.unit_cost', 5);
        foreach ($queries as $sql) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|drop|truncate)\b/i', $sql);
        }
        $this->getJson(route('admin.dashboard.data', ['cost_quality' => 'invalid']))->assertUnprocessable();
    }

    public function test_cost_quality_details_are_paginated_and_do_not_bypass_module_permissions(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create(['cost_rm' => 5]);
        for ($index = 0; $index < 21; $index++) {
            $this->order($product, 'paid', 'completed', 50);
        }
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['cost_quality' => 'all', 'page' => 2]))
            ->assertOk()->assertJsonPath('transactions.total', 21)->assertJsonCount(1, 'transactions.rows');
        $staff = AdminUser::factory()->create();
        $role = AdminRole::factory()->create(['permissions' => ['dashboard.view']]);
        $staff->accessRoles()->attach($role);
        $this->actingAs($staff, 'admin')->getJson(route('admin.dashboard.data', ['cost_quality' => 'all']))
            ->assertOk()->assertJsonPath('transactions.total', 0)->assertJsonCount(0, 'transactions.rows');
    }

    public function test_stock_quality_filters_are_grouped_and_read_only(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        Product::factory()->create(['prd_name' => 'Complete', 'cost_rm' => 0, 'price_selling' => 0, 'prd_balance' => 5]);
        Product::factory()->create(['prd_name' => 'Missing cost', 'cost_rm' => null, 'price_selling' => 10, 'prd_balance' => 5]);
        Product::factory()->create(['prd_name' => 'Missing price', 'cost_rm' => 2, 'price_selling' => null, 'prd_balance' => 5]);
        Product::factory()->create(['prd_name' => 'Negative', 'cost_rm' => 2, 'price_selling' => 10, 'prd_balance' => -2]);
        $overlap = Product::factory()->create(['prd_name' => 'Both issues', 'cost_rm' => null, 'price_selling' => 10, 'prd_balance' => -1]);
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.inventory', ['stock_quality' => 'all']))
            ->assertOk()->assertJsonPath('summary.records', 4)->assertJsonPath('summary.unknown', 3)->assertJsonPath('summary.negative', 2);
        $this->getJson(route('admin.dashboard.inventory', ['stock_quality' => 'missing']))
            ->assertOk()->assertJsonPath('summary.records', 3);
        $this->getJson(route('admin.dashboard.inventory', ['stock_quality' => 'negative']))
            ->assertOk()->assertJsonPath('summary.records', 2)->assertJsonPath('summary.asset', 0);
        $this->getJson(route('admin.dashboard.inventory', ['stock_quality' => 'all', 'stock_search' => 'Missing', 'stock_status' => 'healthy']))
            ->assertOk()->assertJsonPath('summary.records', 2)->assertJsonPath('summary.negative', 0);
        $this->getJson(route('admin.dashboard.inventory', ['stock_quality' => 'negative', 'stock_status' => 'healthy']))
            ->assertOk()->assertJsonPath('summary.records', 0);
        $this->assertSame(-1, (int) $overlap->fresh()->prd_balance);
        foreach ($queries as $sql) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|drop|truncate)\b/i', $sql);
        }
        $this->getJson(route('admin.dashboard.inventory', ['stock_quality' => 'invalid']))->assertUnprocessable();
    }

    public function test_stock_quality_is_paginated_and_requires_product_access(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        Product::factory()->count(21)->create(['cost_rm' => null]);
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.inventory', ['stock_quality' => 'missing', 'stock_page' => 2]))
            ->assertOk()->assertJsonPath('summary.records', 21)->assertJsonCount(1, 'rows');
        $staff = AdminUser::factory()->create();
        $role = AdminRole::factory()->create(['permissions' => ['dashboard.view']]);
        $staff->accessRoles()->attach($role);
        $this->actingAs($staff, 'admin')->getJson(route('admin.dashboard.inventory', ['stock_quality' => 'all']))->assertForbidden();
    }

    public function test_clicker_report_uses_complete_casing_cost_and_explains_missing_variants(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $site = BusinessSite::create(['site_name' => 'Clicker Site', 'city' => 'Klang']);
        $product = Product::factory()->create(['product_type' => 'clicker', 'cost_rm' => 999]);
        DB::table('product_clicker_prices')->insert(['product_id' => $product->id, 'character_count' => 3, 'price_rm' => 9, 'cost_rm' => 3.30]);
        $sale = $this->sale($product, $site, '2026-09-02', 45, 1);
        DB::table('pos_sale_items')->where('pos_sale_id', $sale)->update(['quantity' => 5, 'unit_cost' => null, 'clicker_configuration' => json_encode(['character_count' => 3])]);
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['channel' => 'pos', 'cost_quality' => 'all']))
            ->assertOk()->assertJsonPath('summary.capital', 16.5)->assertJsonPath('summary.missing', 0)
            ->assertJsonPath('transactions.rows.0.cost_items.0.unit_cost', 3.3)
            ->assertJsonPath('transactions.rows.0.cost_items.0.total_cost', 16.5)
            ->assertJsonPath('transactions.rows.0.cost_items.0.source', 'current')
            ->assertJsonPath('products.0.profit', 28.5);
        DB::table('pos_sale_items')->where('pos_sale_id', $sale)->update(['unit_cost' => 2]);
        $this->getJson(route('admin.dashboard.data', ['channel' => 'pos']))
            ->assertOk()->assertJsonPath('summary.capital', 10)->assertJsonPath('summary.estimated', 0);
        DB::table('pos_sale_items')->where('pos_sale_id', $sale)->update(['unit_cost' => null, 'clicker_configuration' => null]);
        $this->getJson(route('admin.dashboard.data', ['channel' => 'pos', 'cost_quality' => 'missing']))
            ->assertOk()->assertJsonPath('summary.capital', 0)->assertJsonPath('summary.missing', 5)
            ->assertJsonPath('transactions.rows.0.cost_items.0.source', 'variant_missing');
        DB::table('pos_sale_items')->where('pos_sale_id', $sale)->update(['clicker_configuration' => json_encode(['character_count' => 8])]);
        $this->getJson(route('admin.dashboard.data', ['channel' => 'pos', 'cost_quality' => 'missing']))
            ->assertOk()->assertJsonPath('transactions.rows.0.cost_items.0.source', 'missing');
        $this->order($product, 'paid', 'completed', 50);
        $this->getJson(route('admin.dashboard.data', ['channel' => 'orders', 'cost_quality' => 'missing']))
            ->assertOk()->assertJsonPath('summary.capital', 0)
            ->assertJsonPath('transactions.rows.0.cost_items.0.source', 'variant_missing');
    }

    public function test_complete_character_prices_are_not_classified_as_missing_cost_when_stock_is_unallocated(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create(['product_type' => 'clicker', 'casing_stock_enabled' => false, 'prd_balance' => 11, 'cost_rm' => null, 'price_selling' => null]);
        foreach (range(1, 8) as $count) {
            DB::table('product_clicker_prices')->insert(['product_id' => $product->id, 'character_count' => $count, 'cost_rm' => $count, 'price_rm' => $count + 5]);
        }
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.inventory', ['stock_quality' => 'missing']))
            ->assertOk()->assertJsonPath('summary.records', 0);
        $this->getJson(route('admin.dashboard.inventory', ['stock_quality' => 'allocation']))
            ->assertOk()->assertJsonPath('summary.records', 1)->assertJsonPath('summary.pricing_missing', 0)
            ->assertJsonPath('rows.0.allocation_missing', true)->assertJsonPath('rows.0.pricing_missing', false)
            ->assertJsonPath('rows.0.asset', null)->assertJsonPath('rows.0.available', 11);
        $this->getJson(route('admin.dashboard.inventory', ['stock_quality' => 'all']))
            ->assertOk()->assertJsonPath('summary.records', 1);
        DB::table('product_clicker_prices')->where('product_id', $product->id)->where('character_count', 3)->update(['cost_rm' => null]);
        $this->getJson(route('admin.dashboard.inventory', ['stock_quality' => 'missing']))
            ->assertOk()->assertJsonPath('summary.records', 1)->assertJsonPath('rows.0.pricing_missing', true);
        $this->assertSame(11, (int) $product->fresh()->prd_balance);
    }

    public function test_reservation_details_match_stock_totals_and_respect_access(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create(['cost_rm' => 3.3, 'prd_balance' => 5]);
        $this->order($product, 'paid', 'processing', 30);
        $this->order($product, 'paid', 'completed', 30);
        $this->order($product, 'refunded', 'processing', 30);
        $this->order($product, 'paid', 'pending', 30, true);
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.inventory', ['stock_reservations' => 1]))
            ->assertOk()->assertJsonPath('total', 2)->assertJsonPath('units', 4)->assertJsonPath('asset', 13.2)
            ->assertJsonPath('rows.0.total_cost', 6.6)->assertJsonPath('restricted', false);
        $this->getJson(route('admin.dashboard.inventory'))
            ->assertOk()->assertJsonPath('summary.reserved', 4)->assertJsonPath('summary.reserved_asset', 13.2);
        $this->getJson(route('admin.dashboard.inventory', ['stock_reservations' => 1, 'stock_search' => 'NO-MATCH-123']))
            ->assertOk()->assertJsonPath('total', 0)->assertJsonPath('units', 0);
        foreach ($queries as $sql) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|drop|truncate)\b/i', $sql);
        }
        $staff = AdminUser::factory()->create();
        $role = AdminRole::factory()->create(['permissions' => ['dashboard.view', 'products.view', 'orders.view']]);
        $staff->accessRoles()->attach($role);
        $this->actingAs($staff, 'admin')->getJson(route('admin.dashboard.inventory', ['stock_reservations' => 1]))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('units', 2)->assertJsonPath('restricted', true);
        $role->update(['permissions' => ['dashboard.view', 'products.view']]);
        $staff->unsetRelation('accessRoles');
        $this->actingAs($staff->fresh(), 'admin')->getJson(route('admin.dashboard.inventory', ['stock_reservations' => 1]))->assertForbidden();
    }

    public function test_custom_date_range_filters_totals_chart_transactions_and_export_across_years(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $site = BusinessSite::create(['site_name' => 'Date Site', 'city' => 'Klang']);
        $product = Product::factory()->create(['cost_rm' => 5]);
        $this->sale($product, $site, '2025-12-31 23:59:59', 20, 5);
        $this->sale($product, $site, '2026-01-01 00:00:00', 30, 5);
        $this->sale($product, $site, '2026-01-02 00:00:00', 90, 5);
        $filters = ['start_date' => '2025-12-31', 'end_date' => '2026-01-01', 'channel' => 'pos'];
        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', $filters));
        $response->assertOk()->assertJsonPath('summary.sales', 50)->assertJsonPath('transactions.total', 2)
            ->assertJsonPath('granularity', 'month')->assertJsonCount(2, 'series')
            ->assertJsonPath('series.0.start', '2025-12-31')->assertJsonPath('series.0.sales', 20)->assertJsonPath('series.0.pos_sales', 20)->assertJsonPath('series.0.order_sales', 0)->assertJsonPath('series.0.customer_sales', 0)
            ->assertJsonPath('series.1.start', '2026-01-01')->assertJsonPath('series.1.sales', 30)
            ->assertJsonPath('period.previous_start', '2025-12-29')
            ->assertJsonPath('period.previous_end', '2025-12-30 23:59:59');
        $csv = $this->get(route('admin.dashboard.export', $filters))->assertOk()->streamedContent();
        $this->assertStringContainsString('2025-12-31', $csv);
        $this->assertStringNotContainsString('2026-01-02', $csv);
        $this->getJson(route('admin.dashboard.data', [...$filters, 'start_date' => '2026-01-01']))
            ->assertOk()->assertJsonPath('summary.sales', 30)->assertJsonPath('granularity', 'day')->assertJsonCount(1, 'series');
        $this->getJson(route('admin.dashboard.data', [...$filters, 'comparison' => 'year']))
            ->assertOk()->assertJsonPath('period.previous_start', '2024-12-31')->assertJsonPath('period.previous_end', '2025-01-01 23:59:59');
    }

    public function test_custom_date_range_rejects_invalid_or_incomplete_dates(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $this->actingAs($admin, 'admin');
        foreach ([
            ['start_date' => '2026-01-01'],
            ['end_date' => '2026-01-01'],
            ['start_date' => '2026-02-30', 'end_date' => '2026-03-01'],
            ['start_date' => '2026-02-01', 'end_date' => '2026-01-01'],
            ['start_date' => '2026-01-01', 'end_date' => '2027-01-01'],
            ['start_date' => '2026-01-01', 'end_date' => '2026-02-01', 'month' => 1],
        ] as $filters) {
            $this->getJson(route('admin.dashboard.data', $filters))->assertUnprocessable();
        }
    }

    public function test_agent_discount_potential_is_filtered_without_changing_company_profit(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create(['cost_rm' => 3]);
        $included = $this->order($product, 'paid', 'completed', 22);
        $unpaid = $this->order($product, 'unpaid', 'pending', 22);
        $cancelled = $this->order($product, 'paid', 'cancelled', 22);
        DB::table('order_items')->whereIn('order_id', [$included, $unpaid, $cancelled])->update(['unit_selling_price' => 15]);
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['start_date' => '2026-09-01', 'end_date' => '2026-09-03']))
            ->assertOk()->assertJsonPath('summary.agent_discount_potential', 8)
            ->assertJsonPath('summary.sales', 22)->assertJsonPath('summary.cost', 6)->assertJsonPath('summary.profit', 16);
        $this->getJson(route('admin.dashboard.data', ['month' => 8]))->assertOk()->assertJsonPath('summary.agent_discount_potential', 0);
        $this->getJson(route('admin.dashboard.data', ['channel' => 'pos']))->assertOk()->assertJsonPath('summary.agent_discount_potential', 0);
        $staff = AdminUser::factory()->create();
        $role = AdminRole::factory()->create(['permissions' => ['dashboard.view']]);
        $staff->accessRoles()->attach($role);
        $this->actingAs($staff, 'admin')->getJson(route('admin.dashboard.data'))->assertOk()->assertJsonPath('summary.agent_discount_potential', 0);
    }

    public function test_donut_product_totals_include_products_beyond_ranking_and_separate_losses(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $site = BusinessSite::create(['site_name' => 'Chart Site', 'city' => 'Klang']);
        foreach (range(1, 7) as $index) {
            $this->sale(Product::factory()->create(), $site, '2026-09-02', 100, 10);
        }
        $this->sale(Product::factory()->create(['prd_name' => 'Loss product']), $site, '2026-09-02', 10, 20);
        $other = BusinessSite::create(['site_name' => 'Excluded Site', 'city' => 'Klang']);
        $this->sale(Product::factory()->create(), $other, '2026-09-02', 500, 0);
        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['site' => $site->id, 'month' => 9, 'page' => 2]));
        $response->assertOk()->assertJsonCount(5, 'products')->assertJsonCount(0, 'transactions.rows')
            ->assertJsonPath('product_distribution.positive_total', 560)
            ->assertJsonPath('product_distribution.other_profit', 160)
            ->assertJsonPath('product_distribution.loss_total', -30)
            ->assertJsonPath('product_distribution.losses.0.name', 'Loss product')
            ->assertJsonPath('product_distribution.losses.0.profit', -30)
            ->assertJsonPath('transaction_channels.0.sales', 710);
        $this->assertEquals(560, array_sum(array_column($response->json('products'), 'profit')) + $response->json('product_distribution.other_profit'));
    }

    public function test_transaction_donut_respects_channel_and_empty_period_filters(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create(['cost_rm' => 5]);
        $site = BusinessSite::create(['site_name' => 'Chart Site', 'city' => 'Klang']);
        $this->sale($product, $site, '2026-09-02', 50, 5);
        $this->order($product, 'paid', 'completed', 100);
        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['channel' => 'orders']))->assertOk()
            ->assertJsonCount(1, 'transaction_channels')->assertJsonPath('transaction_channels.0.key', 'orders')
            ->assertJsonPath('transaction_channels.0.sales', 100);
        $this->getJson(route('admin.dashboard.data', ['month' => 1]))->assertOk()
            ->assertJsonCount(0, 'transaction_channels')->assertJsonCount(0, 'products')
            ->assertJsonPath('product_distribution.positive_total', 0)->assertJsonPath('product_distribution.other_profit', 0)
            ->assertJsonCount(0, 'product_distribution.losses');
        $viewer = AdminUser::factory()->create();
        $viewer->accessRoles()->attach(AdminRole::factory()->create(['permissions' => ['dashboard.view']]));
        $this->actingAs($viewer, 'admin')->getJson(route('admin.dashboard.data'))->assertOk()
            ->assertJsonCount(0, 'transaction_channels')->assertJsonPath('product_distribution.positive_total', 0);
    }

    public function test_pos_chart_uses_same_report_and_session_dates_as_sales(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $site = BusinessSite::create(['site_name' => 'Session Date Site', 'city' => 'Klang']);
        $product = Product::factory()->create(['cost_rm' => 5]);
        $sessionSale = $this->sale($product, $site, '2026-09-03 23:00:00', 20, 5);
        $operationId = DB::table('pos_sales')->where('id', $sessionSale)->value('business_site_operation_id');
        DB::table('business_site_operations')->where('id', $operationId)->update(['opened_at' => '2026-08-09 18:00:00']);
        $reportSale = $this->sale($product, $site, '2026-09-03 23:00:00', 30, 5);
        DB::table('pos_sales')->where('id', $reportSale)->update(['report_date' => '2026-09-02']);
        $this->sale($product, $site, '2026-09-03 23:59:59', 40, 5);
        $voidSale = $this->sale($product, $site, '2026-09-03 23:00:00', 900, 5, true);
        DB::table('pos_sales')->where('id', $voidSale)->update(['report_date' => '2026-09-02']);

        $this->actingAs($admin, 'admin')->getJson(route('admin.dashboard.data', ['year' => 2026, 'month' => 9, 'channel' => 'pos']))
            ->assertOk()->assertJsonPath('summary.sales', 70)->assertJsonPath('previous.sales', 20)
            ->assertJsonPath('series.1.sales', 30)->assertJsonPath('series.2.sales', 40);
        foreach (['2026-08-09' => 20, '2026-09-02' => 30, '2026-09-03' => 40] as $date => $amount) {
            $filters = ['start_date' => $date, 'end_date' => $date, 'channel' => 'pos'];
            $this->getJson(route('admin.dashboard.data', $filters))->assertOk()
                ->assertJsonPath('summary.sales', $amount)->assertJsonPath('series.0.sales', $amount)
                ->assertJsonPath('transactions.total', 1);
            $this->get(route('admin.sales.index', ['single_date' => $date]))->assertOk()
                ->assertViewHas('summary', fn (array $summary): bool => $summary['total_amount'] === (float) $amount);
            $csv = $this->get(route('admin.dashboard.export', $filters))->assertOk()->streamedContent();
            $this->assertStringContainsString($date, $csv);
        }
        $this->getJson(route('admin.dashboard.data', ['year' => 2026, 'month' => 9, 'channel' => 'pos', 'day' => 2]))
            ->assertOk()->assertJsonPath('transactions.total', 1)
            ->assertJsonPath('transactions.rows.0.sales', 30);
    }

    private function sale(Product $product, BusinessSite $site, string $date, int $amount, int $cost, bool $void = false): int
    {
        $agent = Agent::factory()->create();
        $operation = DB::table('business_site_operations')->insertGetId(['business_site_id' => $site->id, 'opened_at' => $date]);
        $session = DB::table('pos_sessions')->insertGetId(['agent_id' => $agent->id, 'business_site_id' => $site->id, 'signed_in_at' => $date, 'expires_at' => now()->addDay()]);
        $id = DB::table('pos_sales')->insertGetId(['business_site_operation_id' => $operation, 'pos_session_id' => $session, 'recorded_by_agent_id' => $agent->id, 'sales_agent_id' => $agent->id, 'sale_number' => 'POS-'.str()->uuid(), 'business_site_id' => $site->id, 'payment_method' => 'cash', 'total_amount' => $amount, 'sold_at' => $date, 'voided_at' => $void ? now() : null, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pos_sale_items')->insert(['pos_sale_id' => $id, 'product_id' => $product->id, 'product_name' => $product->prd_name, 'product_code' => $product->prd_code, 'quantity' => 2, 'unit_price' => $amount / 2, 'unit_cost' => $cost, 'agent_discount_percentage' => 0, 'agent_discount_amount' => 0, 'customer_discount_amount' => 0, 'line_total' => $amount, 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }

    private function order(Product $product, string $payment, string $status, int $amount, bool $customer = false): int
    {
        $agent = Agent::factory()->create();
        $data = ['idempotency_key' => (string) str()->uuid(), 'order_number' => 'ORDER-'.str()->uuid(), 'agent_id' => $agent->id, 'status' => $status, 'payment_status' => $payment, 'fulfilment_method' => 'pickup', 'recipient_name' => 'Recipient', 'phone_number' => '0123456789', 'payment_method' => 'bank_transfer', 'subtotal' => $amount, 'delivery_fee' => 5, 'total_amount' => $amount + 5, 'total_units' => 2, 'placed_at' => '2026-09-02 09:00:00', 'created_at' => now(), 'updated_at' => now()];
        if ($customer) {
            $data['tracking_token'] = (string) str()->uuid();
        }
        $id = DB::table($customer ? 'customer_orders' : 'orders')->insertGetId($data);
        DB::table($customer ? 'customer_order_items' : 'order_items')->insert(['order_id' => $id, 'product_id' => $product->id, 'product_name' => $product->prd_name, 'product_code' => $product->prd_code, 'quantity' => 2, 'reserved_quantity' => 2, 'unit_selling_price' => $amount / 2, 'unit_price' => $amount / 2, 'line_total' => $amount, 'discount_percentage' => 0, 'is_preorder' => false, 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }
}
