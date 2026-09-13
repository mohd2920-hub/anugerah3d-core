<?php

namespace Tests\Feature;

use App\Actions\Pos\CreatePosSale;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\Order;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use App\Support\ProductStockOverview;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductDiscontinuationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pages_and_checkout_work_before_discontinuation_columns_are_available(): void
    {
        $product = Product::factory()->create(['prd_balance' => 4, 'is_visible_to_agents' => true]);
        [$agent, $session] = $this->posSession();
        $admin = AdminUser::factory()->superAdmin()->create();
        Mail::fake();
        $schema = Schema::getFacadeRoot();
        $schemaMock = \Mockery::mock($schema);
        $schemaMock->shouldReceive('hasColumn')->andReturnUsing(
            fn (string $table, string $column): bool => $table === 'products' && $column === 'discontinued_at' ? false : $schema->hasColumn($table, $column),
        );
        Schema::swap($schemaMock);
        $dispatcher = Product::getEventDispatcher();
        Product::setEventDispatcher(clone $dispatcher);
        Product::retrieved(function (Product $product): void {
            $attributes = $product->getAttributes();
            unset($attributes['discontinued_at'], $attributes['discontinuation_reason']);
            $product->setRawAttributes($attributes, true);
        });

        try {
            $this->actingAs($admin, 'admin')->get(route('admin.products.index'))
                ->assertOk()->assertSee($product->prd_name)->assertDontSee('Sahkan Hentikan Produk');
            $this->actingAs($agent, 'agent')->get(route('agent.orders.create'))->assertOk();
            $this->get(route('agent.pos.index'))->assertOk();
            $this->postJson(route('agent.orders.store'), $this->orderData($product, 1))->assertCreated();
            $sale = app(CreatePosSale::class)->handle($session, ['sales_agent_id' => $agent->id, 'payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => 1]]]);
            $this->assertTrue($sale->items()->sole()->uses_product_stock);
            $this->assertSame(2, $product->fresh()->prd_balance);
        } finally {
            Product::setEventDispatcher($dispatcher);
            Schema::swap($schema);
        }
    }

    public function test_discontinued_stock_filter_counts_products_and_includes_hidden_stock(): void
    {
        $visible = $this->discontinuedProduct(4);
        $hidden = $this->discontinuedProduct(2);
        $hidden->update(['is_visible_to_agents' => false]);
        $this->discontinuedProduct(0);
        Product::factory()->create(['prd_balance' => 9]);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $url = route('admin.products.index', ['stock' => 'discontinued-stock']);
        $this->get($url)->assertOk()->assertSee('Dihentikan — Masih Ada Stok')
            ->assertViewHas('discontinuedStockCount', 2)
            ->assertViewHas('includeHidden', false)
            ->assertViewHas('products', fn ($products): bool => $products->total() === 2 && $products->pluck('id')->sort()->values()->all() === collect([$visible->id, $hidden->id])->sort()->values()->all());
        $this->get($url.'&search='.urlencode($hidden->prd_code))->assertOk()
            ->assertViewHas('products', fn ($products): bool => $products->total() === 1 && $products->first()->id === $hidden->id);
        $visible->update(['prd_balance' => 0]);
        $hidden->update(['prd_balance' => 0]);
        $this->get($url)->assertOk()->assertViewHas('discontinuedStockCount', 0)
            ->assertViewHas('products', fn ($products): bool => $products->total() === 0);
    }

    public function test_admin_can_discontinue_and_reactivate_without_changing_stock_or_manual_visibility(): void
    {
        $product = Product::factory()->create(['prd_balance' => 3, 'is_visible_to_agents' => false]);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $this->patch(route('admin.products.discontinuation.update', $product), ['discontinued' => true, 'reason' => 'Pengeluar berhenti'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($product->fresh()->discontinued_at);
        $this->assertSame('Pengeluar berhenti', $product->fresh()->discontinuation_reason);
        $this->assertSame(3, $product->fresh()->prd_balance);
        $this->assertFalse($product->fresh()->is_visible_to_agents);
        $this->get(route('admin.products.index', ['stock' => 'discontinued']))->assertOk()->assertSee('Aktifkan Semula Produk')->assertSee('Dihentikan — Jual Baki Stok');
        $this->assertSame(0, app(ProductStockOverview::class)->filtered('low', '', true)->count());
        $this->patch(route('admin.products.discontinuation.update', $product), ['discontinued' => false])->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->discontinued_at);
        $this->assertFalse($product->fresh()->is_visible_to_agents);
        $this->assertSame(1, app(ProductStockOverview::class)->filtered('low', '', true)->count());
    }

    public function test_invalid_status_and_staff_without_product_edit_permission_cannot_discontinue(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin')
            ->patch(route('admin.products.discontinuation.update', $product), ['discontinued' => 'invalid'])->assertSessionHasErrors('discontinued');
        $this->actingAs(AdminUser::factory()->create(), 'admin')
            ->patch(route('admin.products.discontinuation.update', $product), ['discontinued' => true])->assertForbidden();
        $this->assertNull($product->fresh()->discontinued_at);
    }

    public function test_last_units_can_be_ordered_but_sold_out_product_cannot_be_preordered(): void
    {
        $product = $this->discontinuedProduct(2);
        $agent = Agent::factory()->create();
        Mail::fake();
        $this->actingAs($agent, 'agent');
        $this->get(route('agent.orders.create'))->assertOk()->assertSee('Stok terakhir — sehingga habis stok');
        $data = $this->orderData($product, 2);
        $this->postJson(route('agent.orders.store'), $data)->assertCreated();
        $this->assertSame(0, $product->fresh()->prd_balance);
        $this->assertFalse(Order::query()->sole()->items()->sole()->is_preorder);
        $this->assertFalse(Product::query()->visibleToAgents()->whereKey($product)->exists());
        $this->postJson(route('agent.orders.store'), $this->orderData($product, 1))->assertRedirect()->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 1);
        $this->assertTrue($product->fresh()->is_visible_to_agents);
        $this->postJson(route('agent.orders.store'), $data)->assertCreated();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_checkout_rejects_quantity_above_remaining_stock(): void
    {
        $product = $this->discontinuedProduct(1);
        Mail::fake();
        $this->actingAs(Agent::factory()->create(), 'agent')
            ->postJson(route('agent.orders.store'), $this->orderData($product, 2))->assertRedirect()->assertSessionHasErrors('items');
        $this->assertSame(1, $product->fresh()->prd_balance);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_pos_debits_last_stock_and_void_restores_it_even_after_reactivation(): void
    {
        $product = $this->discontinuedProduct(2);
        [$agent, $session] = $this->posSession();
        $this->actingAs($agent, 'agent');
        $data = ['sales_agent_id' => $agent->id, 'payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => 2]]];
        $this->post(route('agent.pos.sales.store'), $data)->assertSessionHasNoErrors();
        $sale = PosSale::query()->sole();
        $this->assertTrue($sale->items()->sole()->uses_product_stock);
        $this->assertSame(0, $product->fresh()->prd_balance);
        $this->post(route('agent.pos.sales.store'), $data)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('pos_sales', 1);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $preview = $this->post(route('admin.sales.preview', $sale), [
            'action' => 'correct', 'reason' => 'Correct quantity', 'sold_at' => $sale->sold_at->format('Y-m-d\TH:i'),
            'sales_agent_id' => $agent->id, 'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'sale_item_id' => $sale->items()->sole()->id, 'quantity' => 1]],
        ])->assertOk();
        $this->assertSame(0, $product->fresh()->prd_balance);
        $this->post(route('admin.sale-corrections.store'), ['token' => $preview->viewData('token')])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, $product->fresh()->prd_balance);
        $this->assertTrue($sale->fresh()->items()->sole()->uses_product_stock);
        $product->forceFill(['discontinued_at' => null])->save();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $preview = $this->post(route('admin.sales.preview', $sale), ['action' => 'void', 'reason' => 'Wrong transaction'])->assertOk();
        $token = $preview->viewData('token');
        $this->post(route('admin.sale-corrections.store'), ['token' => $token])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, $product->fresh()->prd_balance);
        $this->post(route('admin.sale-corrections.store'), ['token' => $token])->assertStatus(419);
        $this->assertSame(2, $product->fresh()->prd_balance);
    }

    public function test_active_product_deducts_central_stock(): void
    {
        $product = Product::factory()->create(['prd_balance' => 2]);
        [$agent, $session] = $this->posSession();
        $sale = app(CreatePosSale::class)->handle($session, ['sales_agent_id' => $agent->id, 'payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => 1]]]);
        $this->assertTrue($sale->items()->sole()->uses_product_stock);
        $this->assertSame(1, $product->fresh()->prd_balance);
    }

    public function test_active_pos_rejects_oversell_without_saving_or_deducting(): void
    {
        $product = Product::factory()->create(['prd_balance' => 3]);
        [$agent] = $this->posSession();
        $this->actingAs($agent, 'agent')->post(route('agent.pos.sales.store'), [
            'sales_agent_id' => $agent->id, 'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 4]],
        ])->assertSessionHasErrors('items');
        $this->assertSame(3, $product->fresh()->prd_balance);
        $this->assertDatabaseCount('pos_sales', 0);
        $this->assertDatabaseCount('pos_sale_items', 0);
    }

    public function test_active_pos_correction_applies_difference_and_void_restores_once(): void
    {
        $product = Product::factory()->create(['prd_balance' => 5]);
        [$agent, $session] = $this->posSession();
        $sale = app(CreatePosSale::class)->handle($session, ['sales_agent_id' => $agent->id, 'payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => 2]]]);
        $this->assertSame(3, $product->fresh()->prd_balance);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        foreach ([3 => 2, 1 => 4] as $quantity => $balance) {
            $preview = $this->post(route('admin.sales.preview', $sale), [
                'action' => 'correct', 'reason' => 'Correct quantity', 'sold_at' => $sale->sold_at->format('Y-m-d\TH:i'),
                'sales_agent_id' => $agent->id, 'payment_method' => 'cash',
                'items' => [['product_id' => $product->id, 'sale_item_id' => $sale->items()->sole()->id, 'quantity' => $quantity]],
            ])->assertOk();
            $this->post(route('admin.sale-corrections.store'), ['token' => $preview->viewData('token')])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($balance, $product->fresh()->prd_balance);
            $sale->refresh();
        }
        $preview = $this->post(route('admin.sales.preview', $sale), ['action' => 'void', 'reason' => 'Wrong transaction'])->assertOk();
        $token = $preview->viewData('token');
        $this->post(route('admin.sale-corrections.store'), ['token' => $token])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(5, $product->fresh()->prd_balance);
        $this->post(route('admin.sale-corrections.store'), ['token' => $token])->assertStatus(419);
        $this->assertSame(5, $product->fresh()->prd_balance);
    }

    private function discontinuedProduct(int $stock): Product
    {
        $product = Product::factory()->create(['product_type' => 'standard', 'prd_balance' => $stock, 'is_visible_to_agents' => true]);
        $product->forceFill(['discontinued_at' => now()])->save();

        return $product;
    }

    private function orderData(Product $product, int $quantity): array
    {
        return ['idempotency_key' => (string) Str::uuid(), 'fulfilment_method' => 'delivery', 'recipient_name' => 'Test Customer', 'phone_number' => '0123456789', 'delivery_address' => '123 Test Street', 'payment_method' => 'pay_later', 'items' => [['product_id' => $product->id, 'quantity' => $quantity]]];
    }

    private function posSession(): array
    {
        $agent = Agent::factory()->create();
        $site = BusinessSite::query()->create(['site_name' => 'Test site', 'city' => 'Klang', 'opened_at' => now()->subHour()]);
        $site->agents()->attach($agent);
        BusinessSiteOperation::query()->create(['business_site_id' => $site->id, 'opened_at' => now()->subHour()]);
        $session = PosSession::query()->create(['agent_id' => $agent->id, 'business_site_id' => $site->id, 'signed_in_at' => now()->subHour()]);

        return [$agent, $session];
    }
}
