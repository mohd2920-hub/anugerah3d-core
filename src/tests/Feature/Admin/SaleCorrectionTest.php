<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SaleCorrectionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private AdminUser $admin;

    private Agent $agent;

    private BusinessSiteOperation $operation;

    private Product $product;

    private PosSale $sale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->superAdmin()->create();
        $this->agent = Agent::factory()->create();
        $site = BusinessSite::query()->create(['site_name' => 'Correction site', 'city' => 'Klang', 'opened_at' => now()->subDays(2)]);
        $site->agents()->attach($this->agent);
        $this->operation = BusinessSiteOperation::query()->create(['business_site_id' => $site->id, 'opened_at' => now()->subDays(2), 'closed_at' => now()->subDay()]);
        $session = PosSession::query()->create(['agent_id' => $this->agent->id, 'business_site_id' => $site->id, 'signed_in_at' => now()->subDays(2)]);
        $this->product = Product::factory()->create(['price_selling' => 80, 'cost_rm' => 12, 'prd_balance' => 100]);
        $this->sale = PosSale::query()->create([
            'sale_number' => 'POS-ORIGINAL', 'pos_session_id' => $session->id,
            'business_site_id' => $site->id, 'business_site_operation_id' => $this->operation->id,
            'recorded_by_agent_id' => $this->agent->id, 'sales_agent_id' => $this->agent->id,
            'payment_method' => 'cash', 'total_amount' => 90, 'sold_at' => now()->subDays(2)->addHour(),
        ]);
        $this->sale->items()->create([
            'product_id' => $this->product->id, 'product_code' => $this->product->prd_code,
            'product_name' => $this->product->prd_name, 'quantity' => 2, 'unit_price' => 50,
            'unit_cost' => 10, 'customer_discount_amount' => 10, 'uses_product_stock' => true,
            'agent_discount_percentage' => 0, 'agent_discount_amount' => 0, 'line_total' => 90,
        ]);
    }

    public function test_admin_can_preview_and_correct_a_closed_session_with_audit_and_original_receipt(): void
    {
        $new = Product::factory()->create(['price_selling' => 20, 'cost_rm' => 5, 'prd_balance' => 5]);
        $data = $this->data();
        $data['items'][] = ['product_id' => $new->id, 'quantity' => 2, 'discount_amount' => 0];
        $this->actingAs($this->admin, 'admin')->get(route('admin.sales.edit', $this->sale))->assertOk();
        $response = $this->post(route('admin.sales.preview', $this->sale), $data)->assertOk()->assertSeeText('Before')->assertSeeText('After');
        $this->assertSame('90.00', $this->sale->fresh()->total_amount);
        $after = $response->viewData('comparison')['after'];
        $this->assertEquals(185, $after['net_sales']);
        $this->assertEquals(185, $after['net_company']);
        $this->assertEquals(40, $after['capital']);
        $this->assertEquals(145, $after['gross_profit']);
        $this->post(route('admin.sale-corrections.store'), ['token' => $response->viewData('token')])->assertRedirect(route('admin.sales.show', $this->sale));
        $sale = $this->sale->fresh();
        $this->assertSame('POS-ORIGINAL', $sale->sale_number);
        $this->assertSame('185.00', $sale->total_amount);
        $this->assertSame('Corrected customer', $sale->customer_name);
        $this->assertSame('qr', $sale->payment_method);
        $this->assertSame(99, $this->product->fresh()->prd_balance);
        $this->assertSame(3, $new->fresh()->prd_balance);
        $audit = $sale->corrections()->sole();
        $this->assertSame($this->admin->id, $audit->admin_user_id);
        $this->assertSame($data['reason'], $audit->reason);
        $this->assertEquals(90, $audit->before['net_sales']);
        $this->assertEquals(185, $audit->after['net_sales']);
        $this->get(route('admin.sales.show', $sale))->assertOk()->assertSeeText('Corrected')->assertSeeText('Correction history');
    }

    public function test_admin_can_remove_products_and_reduce_quantity(): void
    {
        $data = $this->data();
        $data['items'] = [['product_id' => $this->product->id, 'quantity' => 1, 'discount_amount' => 0]];
        $extra = Product::factory()->create();
        $this->sale->items()->create(['product_id' => $extra->id, 'product_code' => $extra->prd_code, 'product_name' => $extra->prd_name, 'quantity' => 1, 'unit_price' => 5, 'line_total' => 5]);
        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.sales.preview', $this->sale), $data)->assertOk();
        $this->post(route('admin.sale-corrections.store'), ['token' => $response->viewData('token')])->assertRedirect();
        $this->assertSame(1, $this->sale->items()->count());
        $this->assertSame('50.00', $this->sale->fresh()->total_amount);
    }

    public function test_repeated_corrections_preserve_original_items_without_double_counting(): void
    {
        $original = $this->sale->items()->sole();
        $this->actingAs($this->admin, 'admin');

        foreach ([3, 1] as $quantity) {
            $data = $this->data();
            $data['items'] = [['product_id' => $this->product->id, 'quantity' => $quantity, 'discount_amount' => 0]];
            $preview = $this->post(route('admin.sales.preview', $this->sale), $data)->assertOk();
            $this->post(route('admin.sale-corrections.store'), ['token' => $preview->viewData('token')])->assertRedirect();
        }

        $this->assertSoftDeleted($original);
        $historical = $original->fresh();
        $this->assertSame(2, $historical->quantity);
        $this->assertSame('90.00', $historical->line_total);
        $this->assertSame('50.00', $historical->unit_price);
        $this->assertSame(3, $this->sale->items()->withTrashed()->count());
        $this->assertSame(1, $this->sale->items()->count());
        $this->assertEquals(1, $this->product->posSaleItems()->sum('quantity'));
        $summary = $this->get(route('admin.business-site-operations.show', $this->operation))->assertOk()->viewData('summary');
        $this->assertEquals(1, $summary['items_sold']);
        $this->assertEquals(50, $summary['sales_total']);
        $this->assertEquals(10, $summary['capital_total']);
        $report = $this->get(route('admin.sales.index', ['period' => 'month']))->assertOk()->viewData('summary');
        $this->assertEquals(50, $report['total_amount']);
        $this->assertEquals(10, $report['total_cost']);
        $this->assertSame(2, $this->sale->corrections()->count());

        $preview = $this->post(route('admin.sales.preview', $this->sale), ['action' => 'void', 'reason' => 'Duplicate transaction'])->assertOk();
        $this->post(route('admin.sale-corrections.store'), ['token' => $preview->viewData('token')])->assertRedirect();
        $this->assertSame(3, $this->sale->items()->withTrashed()->count());
        $summary = $this->get(route('admin.business-site-operations.show', $this->operation))->assertOk()->viewData('summary');
        $this->assertEquals(0, $summary['items_sold']);
        $this->assertEquals(0, $summary['sales_total']);
    }

    public function test_history_schema_cannot_be_rolled_back_after_a_correction(): void
    {
        $this->sale->items()->delete();
        $migration = require database_path('migrations/2026_09_10_060203_add_deleted_at_to_pos_sale_items_table.php');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot remove history protection');
        $migration->down();
    }

    public function test_product_with_only_historical_sale_items_cannot_be_deleted(): void
    {
        $this->sale->items()->delete();
        $this->admin->update(['password' => Hash::make('test-password')]);
        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.products.destroy', $this->product), ['delete_password' => 'test-password'])
            ->assertSessionHasErrors('product');
        $this->assertModelExists($this->product);
        $this->assertSame(1, $this->sale->items()->withTrashed()->count());
    }

    public function test_sale_and_attendance_and_operation_cannot_be_permanently_deleted(): void
    {
        foreach ([$this->sale, $this->sale->posSession, $this->operation] as $record) {
            try {
                $record->delete();
                $this->fail('Historical record deletion was allowed.');
            } catch (\LogicException $exception) {
                $this->assertSame('Historical records cannot be deleted.', $exception->getMessage());
            }
            $this->assertModelExists($record);
        }
    }

    public function test_correction_audit_cannot_be_edited_or_deleted(): void
    {
        $preview = $this->actingAs($this->admin, 'admin')->post(route('admin.sales.preview', $this->sale), $this->data())->assertOk();
        $this->post(route('admin.sale-corrections.store'), ['token' => $preview->viewData('token')])->assertRedirect();
        $audit = $this->sale->corrections()->sole();
        $reason = $audit->reason;
        try {
            $audit->update(['reason' => 'Rewritten history']);
            $this->fail('Audit update was allowed.');
        } catch (\LogicException $exception) {
            $this->assertSame('Correction history cannot be changed.', $exception->getMessage());
        }
        $this->assertSame($reason, $audit->fresh()->reason);
        try {
            $audit->delete();
            $this->fail('Audit deletion was allowed.');
        } catch (\LogicException $exception) {
            $this->assertSame('Correction history cannot be deleted.', $exception->getMessage());
        }
        $this->assertModelExists($audit);
    }

    public function test_missing_sale_is_added_to_selected_closed_session_without_fake_agent_attendance(): void
    {
        $data = $this->data();
        $data['action'] = 'missing';
        $this->actingAs($this->admin, 'admin')->get(route('admin.sales.create', $this->operation))->assertOk();
        $response = $this->post(route('admin.sales.preview-missing', $this->operation), $data)->assertOk();
        $token = $response->viewData('token');
        $this->post(route('admin.sale-corrections.store'), ['token' => $token])->assertRedirect();
        $sale = PosSale::query()->latest('id')->first();
        $this->assertNotSame($this->sale->sale_number, $sale->sale_number);
        $this->assertNull($sale->pos_session_id);
        $this->assertNull($sale->recorded_by_agent_id);
        $this->assertSame($this->operation->id, $sale->business_site_operation_id);
        $this->assertSame('235.00', $sale->total_amount);
        $this->get(route('admin.sales.show', $sale))->assertOk();
        $this->post(route('admin.sale-corrections.store'), ['token' => $token])->assertStatus(419);
        $this->assertDatabaseCount('pos_sales', 2);
    }

    public function test_void_preserves_items_and_receipt_but_excludes_sale_from_reports(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.sales.preview', $this->sale), ['action' => 'void', 'reason' => 'Duplicate transaction'])->assertOk();
        $this->post(route('admin.sale-corrections.store'), ['token' => $response->viewData('token')])->assertRedirect();
        $this->assertNotNull($this->sale->fresh()->voided_at);
        $this->assertSame(1, $this->sale->items()->count());
        $report = $this->get(route('admin.sales.index', ['period' => 'month']))->assertOk();
        $this->get(route('admin.sales.transactions', ['period' => 'month']))->assertOk()->assertSeeText('Void');
        $this->assertEquals(0, $report->viewData('summary')['total_amount']);
        $this->assertEquals(0, $report->viewData('summary')['total_cost']);
        $operation = $this->get(route('admin.business-site-operations.show', $this->operation))->assertOk()->assertSeeText('Void');
        $this->assertEquals(0, $operation->viewData('summary')['sales_total']);
        $this->get(route('admin.sales.edit', $this->sale))->assertStatus(409);
        $this->post(route('admin.sales.preview', $this->sale), $this->data())->assertSessionHasErrors('sale');
    }

    public function test_stale_preview_cannot_overwrite_a_more_recent_correction(): void
    {
        $this->actingAs($this->admin, 'admin');
        $first = $this->post(route('admin.sales.preview', $this->sale), $this->data())->assertOk()->viewData('token');
        $second = $this->post(route('admin.sales.preview', $this->sale), $this->data())->assertOk()->viewData('token');
        $this->post(route('admin.sale-corrections.store'), ['token' => $second])->assertRedirect();
        $this->post(route('admin.sale-corrections.store'), ['token' => $first])->assertSessionHasErrors('sale');
        $this->assertSame(1, $this->sale->corrections()->count());
    }

    public function test_invalid_discounts_reason_receipt_and_session_time_are_rejected(): void
    {
        $this->actingAs($this->admin, 'admin');
        $data = $this->data();
        $data['items'][0]['discount_amount'] = 151;
        $this->post(route('admin.sales.preview', $this->sale), $data)->assertSessionHasErrors('items.0.discount_amount');
        $this->post(route('admin.sales.preview', $this->sale), array_replace($this->data(), ['reason' => '   ']))->assertSessionHasErrors('reason');
        $this->post(route('admin.sales.preview', $this->sale), $this->data() + ['sale_number' => 'HACKED', 'total_amount' => 1])->assertSessionHasErrors(['sale_number', 'total_amount']);
        $this->post(route('admin.sales.preview', $this->sale), array_replace($this->data(), ['sold_at' => now()->format('Y-m-d H:i:s')]))->assertSessionHasErrors('sold_at');
        $this->assertDatabaseCount('pos_sale_corrections', 0);
    }

    public function test_agent_cannot_correct_delete_or_access_admin_corrections(): void
    {
        $this->actingAs($this->agent, 'agent');
        $this->get(route('agent.pos.sales.edit', $this->sale))->assertForbidden();
        $this->put(route('agent.pos.sales.update', $this->sale), $this->data())->assertForbidden();
        $this->delete(route('agent.pos.sales.destroy', $this->sale))->assertForbidden();
        $this->get(route('admin.sales.edit', $this->sale))->assertRedirect();
        $this->post(route('admin.sales.preview', $this->sale), $this->data())->assertRedirect();
        $this->assertSame('90.00', $this->sale->fresh()->total_amount);
    }

    public function test_sales_person_can_be_corrected_but_unassigned_agents_are_rejected(): void
    {
        $other = Agent::factory()->create();
        $data = array_replace($this->data(), ['sales_agent_id' => $other->id]);
        $this->actingAs($this->admin, 'admin')->post(route('admin.sales.preview', $this->sale), $data)->assertSessionHasErrors('sales_agent_id');
        $this->operation->businessSite->agents()->attach($other);
        $response = $this->post(route('admin.sales.preview', $this->sale), $data)->assertOk();
        $this->post(route('admin.sale-corrections.store'), ['token' => $response->viewData('token')])->assertRedirect();
        $this->assertSame($other->id, $this->sale->fresh()->sales_agent_id);
        $this->assertSame($this->agent->id, $this->sale->fresh()->recorded_by_agent_id);
    }

    public function test_empty_duplicate_and_zero_quantity_products_are_rejected(): void
    {
        $this->actingAs($this->admin, 'admin');
        $data = $this->data();
        $data['items'] = [];
        $this->post(route('admin.sales.preview', $this->sale), $data)->assertSessionHasErrors('items');
        $data = $this->data();
        $data['items'][] = $data['items'][0];
        $this->post(route('admin.sales.preview', $this->sale), $data)->assertSessionHasErrors('items.0.product_id');
        $data = $this->data();
        $data['items'][0]['quantity'] = 0;
        $this->post(route('admin.sales.preview', $this->sale), $data)->assertSessionHasErrors('items.0.quantity');
    }

    public function test_untracked_historical_standard_sale_cannot_increase_or_restore_stock(): void
    {
        $this->sale->items()->sole()->update(['uses_product_stock' => false]);
        $this->actingAs($this->admin, 'admin');
        $this->post(route('admin.sales.preview', $this->sale), $this->data())->assertSessionHasErrors('items');
        $this->assertSame(100, $this->product->fresh()->prd_balance);
        $preview = $this->post(route('admin.sales.preview', $this->sale), ['action' => 'void', 'reason' => 'Void historical sale'])->assertOk();
        $this->post(route('admin.sale-corrections.store'), ['token' => $preview->viewData('token')])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(100, $this->product->fresh()->prd_balance);
    }

    private function data(): array
    {
        return [
            'action' => 'correct', 'reason' => 'Correct missing quantities',
            'sales_agent_id' => $this->agent->id, 'sold_at' => $this->sale->sold_at->format('Y-m-d H:i:s'),
            'customer_name' => 'Corrected customer', 'customer_email' => 'customer@example.com',
            'payment_method' => 'qr', 'payment_remark' => 'Verified by admin',
            'items' => [['product_id' => $this->product->id, 'quantity' => 3, 'discount_amount' => 5]],
        ];
    }
}
