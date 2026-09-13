<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdminUser;
use App\Models\Product;
use App\Support\ProductStockOverview;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductBalanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_balance_update_audits_and_preserves_discontinuation_and_visibility(): void
    {
        $product = Product::factory()->create(['product_type' => 'standard', 'prd_balance' => 5, 'is_visible_to_agents' => true]);
        $product->forceFill(['discontinued_at' => now()])->save();
        $admin = AdminUser::factory()->superAdmin()->create();
        $this->actingAs($admin, 'admin');
        $url = route('admin.products.balance.show', $product);
        $this->getJson($url)->assertOk()->assertJsonPath('balance', 5);
        $this->patchJson($url, $this->data(5, 0))->assertOk();
        $this->assertSame(0, (int) $product->fresh()->prd_balance);
        $this->assertNotNull($product->fresh()->discontinued_at);
        $this->assertTrue((bool) $product->fresh()->is_visible_to_agents);
        $this->assertFalse(Product::visibleToAgents()->whereKey($product->id)->exists());
        $this->assertSame(0, app(ProductStockOverview::class)->statistics()['discontinued_stock']);
        $log = ActivityLog::where('event', 'admin.product.balance.updated')->sole();
        $this->assertSame($admin->id, $log->admin_user_id);
        $this->assertSame(5, $log->properties['before']);
        $this->assertSame(0, $log->properties['after']);
        $this->patchJson($url, $this->data(0, 3))->assertOk();
        $this->assertTrue(Product::visibleToAgents()->whereKey($product->id)->exists());
        $this->assertSame(1, app(ProductStockOverview::class)->statistics()['discontinued_stock']);
    }

    public function test_stale_and_invalid_updates_do_not_change_stock_or_audit(): void
    {
        $product = Product::factory()->create(['product_type' => 'standard', 'prd_balance' => 4]);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $url = route('admin.products.balance.update', $product);
        $this->patchJson($url, $this->data(5, 9))->assertUnprocessable();
        $this->patchJson($url, $this->data(4, -1))->assertUnprocessable();
        $this->patchJson($url, [...$this->data(4, 9), 'reason' => ''])->assertUnprocessable();
        $this->assertSame(4, (int) $product->fresh()->prd_balance);
        $this->assertSame(0, ActivityLog::where('event', 'admin.product.balance.updated')->count());
    }

    public function test_casing_adjustment_changes_only_selected_size_and_recalculates_total(): void
    {
        $product = Product::factory()->create(['product_type' => 'clicker', 'prd_balance' => 9]);
        $product->forceFill(['casing_stock_enabled' => true])->save();
        $casing = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => 'casing', 'position' => 1, 'alt_text' => 'Blue', 'image_path' => 'blue.jpg']);
        DB::table('product_clicker_stocks')->insert([['casing_image_id' => $casing, 'character_count' => 1, 'quantity' => 4], ['casing_image_id' => $casing, 'character_count' => 2, 'quantity' => 5]]);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $url = route('admin.products.balance.update', $product);
        $this->getJson($url)->assertOk()->assertJsonCount(8, 'variations');
        $this->patchJson($url, $this->data(9, 7))->assertUnprocessable();
        $data = [...$this->data(9, 7), 'expected_quantity' => 4, 'casing_id' => $casing, 'character_count' => 1];
        $this->patchJson($url, [...$data, 'casing_id' => $casing + 999])->assertUnprocessable();
        $this->patchJson($url, [...$data, 'expected_quantity' => 3])->assertUnprocessable();
        $this->patchJson($url, $data)->assertOk();
        $this->assertSame(12, (int) $product->fresh()->prd_balance);
        $this->assertDatabaseHas('product_clicker_stocks', ['casing_image_id' => $casing, 'character_count' => 1, 'quantity' => 7]);
        $this->assertDatabaseHas('product_clicker_stocks', ['casing_image_id' => $casing, 'character_count' => 2, 'quantity' => 5]);
        $this->patchJson($url, $data)->assertUnprocessable();
    }

    public function test_unprivileged_admin_cannot_read_or_write_stock_dialog(): void
    {
        $product = Product::factory()->create(['prd_balance' => 4]);
        $this->actingAs(AdminUser::factory()->create(['role' => 'staff']), 'admin');
        $url = route('admin.products.balance.update', $product);
        $this->getJson($url)->assertForbidden();
        $this->patchJson($url, $this->data(4, 9))->assertForbidden();
        $this->assertSame(4, (int) $product->fresh()->prd_balance);
    }

    private function data(int $before, int $after): array
    {
        return ['expected_balance' => $before, 'expected_quantity' => $before, 'quantity' => $after, 'reason' => 'Kiraan fizikal'];
    }
}
