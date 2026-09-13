<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class CasingStockTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_allocation_preserves_total_and_combination_cards_share_one_named_stock_field(): void
    {
        [$product, $casing, $huruf] = $this->product();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $this->put(route('admin.products.update', $product), $this->allocation($product, $casing, 5, 0))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(5, $product->fresh()->prd_balance);
        $this->assertTrue((bool) $product->fresh()->getRawOriginal('casing_stock_enabled'));
        $this->assertSame(5, $this->balance($casing));
        foreach (range(1, 2) as $index) {
            $otherHuruf = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => 'huruf', 'position' => $index + 1, 'image_path' => "huruf-{$index}.jpg"]);
            DB::table('product_clicker_results')->insert(['product_id' => $product->id, 'casing_image_id' => $casing, 'huruf_image_id' => $otherHuruf, 'name' => "Combination {$index}", 'image_path' => "result-{$index}.jpg"]);
        }
        $response = $this->get(route('admin.products.edit', $product))->assertOk()->assertSeeText('Stok mengikut bilangan huruf');
        $this->assertSame(1, substr_count($response->getContent(), 'name="clicker_images[casing][1][stock][3]"'));
        $this->assertSame(2, substr_count($response->getContent(), 'data-combination-stock="'.$casing.':3"'));
    }

    public function test_initial_allocation_requires_a_reason_even_when_replacing_old_balance(): void
    {
        [$product, $casing] = $this->product();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $data = $this->allocation($product, $casing, 18, 0);
        $data['casing_stock_reason'] = '';
        $this->put(route('admin.products.update', $product), $data)->assertSessionHasErrors('casing_stock_reason');
        $this->assertSame(5, $product->fresh()->prd_balance);
        $this->assertSame(0, $this->balance($casing));
    }

    public function test_initial_physical_count_can_replace_old_total_with_audited_higher_lower_or_zero_stock(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $this->actingAs($admin, 'admin');
        foreach ([18, 4, 0] as $quantity) {
            [$product, $casing] = $this->product();
            $product->update(['prd_balance' => 14]);
            $this->put(route('admin.products.update', $product), $this->allocation($product, $casing, $quantity, 0))
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($quantity, $product->fresh()->prd_balance);
            $this->assertSame($quantity, $this->balance($casing));
            $audit = ActivityLog::query()->where('event', 'admin.product.casing-stock.updated')->latest('id')->firstOrFail();
            $this->assertSame($admin->id, $audit->admin_user_id);
            $this->assertSame(14, $audit->properties['balance_before']);
            $this->assertSame($quantity, $audit->properties['balance_after']);
            $this->assertSame('Physical stock count', $audit->properties['reason']);
        }
    }

    public function test_adjustment_rejects_stale_balance_and_ignores_manual_product_total(): void
    {
        [$product, $casing] = $this->product(true);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $this->put(route('admin.products.update', $product), $this->allocation($product, $casing, 9, 4))->assertSessionHasErrors('casing_stock');
        $data = $this->allocation($product, $casing, 9, 5);
        $data['prd_balance'] = 999;
        $this->put(route('admin.products.update', $product), $data)->assertSessionHasNoErrors();
        $this->assertSame(9, $product->fresh()->prd_balance);
        $this->assertSame(9, $this->balance($casing));
        $audit = ActivityLog::query()->where('event', 'admin.product.casing-stock.updated')->sole();
        $this->assertSame('Physical stock count', $audit->properties['reason']);
        $this->assertSame(5, $audit->properties['before'][$casing][3]);
        $this->assertSame(9, $audit->properties['after'][$casing][3]);

    }

    public function test_invalid_stock_and_foreign_casing_are_rejected(): void
    {
        [$product, $casing] = $this->product(true);
        [, $foreign] = $this->product();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $this->put(route('admin.products.update', $product), $this->allocation($product, $casing, -1, 5))->assertSessionHasErrors('clicker_images.casing.1.stock.3');
        $this->put(route('admin.products.update', $product), $this->allocation($product, $foreign, 5, 0))->assertSessionHasErrors('clicker_images.casing.1.id');
        $this->assertSame(5, $this->balance($casing));
        $this->assertSame(0, $this->balance($foreign));
    }

    public function test_one_set_uses_one_casing_and_cancellation_restores_only_once(): void
    {
        [$product, $casing, $huruf] = $this->product(true);
        $payload = $this->cart($product, $casing, $huruf, [2, 1]);
        $this->postJson(route('agent.orders.store'), $payload)->assertCreated();
        $this->postJson(route('agent.orders.store'), $payload)->assertCreated();
        $order = Order::query()->sole();
        $this->assertSame(2, $this->balance($casing));
        $this->assertSame(2, $product->fresh()->prd_balance);
        $this->assertSame([$casing, $casing], $order->items->pluck('clicker_casing_image_id')->all());
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $this->patch(route('admin.orders.cancel', $order))->assertSessionHasNoErrors();
        $this->assertSame(5, $this->balance($casing));
        $this->assertSame(5, $product->fresh()->prd_balance);
        $this->patch(route('admin.orders.cancel', $order))->assertSessionHasErrors('status');
        $this->assertSame(5, $this->balance($casing));
    }

    public function test_shared_casing_cannot_be_oversold_across_multiple_cart_lines(): void
    {
        [$product, $casing, $huruf] = $this->product(true);
        $this->postJson(route('agent.orders.store'), $this->cart($product, $casing, $huruf, [3, 3]))->assertSessionHasErrors('stock');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $this->balance($casing));
        $this->assertSame(5, $product->fresh()->prd_balance);
    }

    public function test_discontinued_clicker_rejects_empty_size_and_sells_only_remaining_casing_stock(): void
    {
        [$product, $casing, $huruf] = $this->product(true);
        $product->forceFill(['discontinued_at' => now()])->save();
        $empty = $this->cart($product, $casing, $huruf, [1]);
        $empty['items'][0]['clicker_character_count'] = 4;
        $empty['items'][0]['clicker_characters'] = ['S', 'A', 'R', 'A'];
        $this->postJson(route('agent.orders.store'), $empty)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $this->balance($casing));
        $this->postJson(route('agent.orders.store'), $this->cart($product, $casing, $huruf, [5]))->assertCreated();
        $this->assertSame(0, $product->fresh()->prd_balance);
        $this->assertSame(0, $this->balance($casing));
        $this->assertFalse(Product::query()->visibleToAgents()->whereKey($product)->exists());
    }

    public function test_empty_casing_keeps_preorder_rule_and_processing_requires_that_casing(): void
    {
        [$product, $casing, $huruf] = $this->product(true);
        DB::table('product_clicker_stocks')->where('casing_image_id', $casing)->update(['quantity' => 0]);
        $otherCasing = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => 'casing', 'position' => 2, 'image_path' => 'other.jpg']);
        foreach (range(1, 8) as $count) {
            DB::table('product_clicker_stocks')->insert(['casing_image_id' => $otherCasing, 'character_count' => $count, 'quantity' => $count === 3 ? 5 : 0]);
        }
        $this->postJson(route('agent.orders.store'), $this->cart($product, $casing, $huruf, [2]))->assertCreated();
        $order = Order::query()->sole();
        $this->assertSame(0, $order->items->sole()->reserved_quantity);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $this->patch(route('admin.orders.process', $order))->assertSessionHasErrors('stock');
        $data = $this->allocation($product, $casing, 3, 0);
        $data['clicker_images']['casing'][2] = ['id' => $otherCasing, 'name' => 'Other', 'stock' => array_replace(array_fill(1, 8, 0), [3 => 5]), 'stock_expected' => array_replace(array_fill(1, 8, 0), [3 => 5])];
        $this->put(route('admin.products.update', $product), $data)->assertSessionHasNoErrors();
        $this->patch(route('admin.orders.process', $order))->assertSessionHasNoErrors();
        $this->assertSame(1, $this->balance($casing));
        $this->assertSame(6, $product->fresh()->prd_balance);
        $this->assertSame(2, $order->items()->sole()->reserved_quantity);
    }

    public function test_legacy_open_orders_block_activation_without_changing_stock(): void
    {
        [$product, $casing, $huruf] = $this->product();
        $this->postJson(route('agent.orders.store'), $this->cart($product, $casing, $huruf, [1]))->assertCreated();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $this->put(route('admin.products.update', $product), $this->allocation($product, $casing, 4, 0))->assertSessionHasErrors('casing_stock');
        $this->assertSame(4, $product->fresh()->prd_balance);
        $this->assertFalse((bool) $product->fresh()->getRawOriginal('casing_stock_enabled'));
    }

    public function test_staff_without_edit_permission_cannot_change_stock(): void
    {
        [$product, $casing] = $this->product(true);
        $this->actingAs(AdminUser::factory()->create(), 'admin');
        $this->put(route('admin.products.update', $product), $this->allocation($product, $casing, 8, 5))->assertForbidden();
        $this->assertSame(5, $this->balance($casing));
    }

    public function test_stock_requires_all_eight_sizes_and_rejects_a_ninth_size(): void
    {
        [$product, $casing] = $this->product(true);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $data = $this->allocation($product, $casing, 5, 5);
        unset($data['clicker_images']['casing'][1]['stock'][8]);
        $this->put(route('admin.products.update', $product), $data)->assertSessionHasErrors('clicker_images.casing.1.stock');
        $data['clicker_images']['casing'][1]['stock'][8] = 0;
        $data['clicker_images']['casing'][1]['stock'][9] = 1;
        $this->put(route('admin.products.update', $product), $data)->assertSessionHasErrors('clicker_images.casing.1.stock');
        $this->assertSame(5, $this->balance($casing));
    }

    public function test_uploading_new_casing_can_save_its_eight_stock_sizes_together(): void
    {
        [$product, $casing] = $this->product(true);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $data = $this->allocation($product, $casing, 5, 5);
        $data['clicker_images']['casing'][2] = ['name' => 'New casing', 'image' => UploadedFile::fake()->image('new-casing.jpg'), 'stock' => array_fill(1, 8, 2), 'stock_expected' => array_fill(1, 8, 0)];
        $this->put(route('admin.products.update', $product), $data)->assertRedirect()->assertSessionHasNoErrors();
        $image = DB::table('product_clicker_images')->where('product_id', $product->id)->where('image_type', 'casing')->where('position', 2)->sole();
        $this->assertSame(16, (int) DB::table('product_clicker_stocks')->where('casing_image_id', $image->id)->sum('quantity'));
        $this->assertSame(21, $product->fresh()->prd_balance);
        File::delete(public_path($image->image_path));
    }

    /** @return array{Product, int, int} */
    private function product(bool $enabled = false): array
    {
        $product = Product::factory()->create(['product_type' => 'clicker', 'prd_balance' => 5]);
        $product->forceFill(['casing_stock_enabled' => $enabled])->save();
        $ids = [];
        foreach (['casing', 'huruf'] as $type) {
            $ids[] = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => $type, 'position' => 1, 'image_path' => "{$type}.jpg", 'alt_text' => $type]);
        }
        foreach (range(1, 8) as $count) {
            DB::table('product_clicker_stocks')->insert(['casing_image_id' => $ids[0], 'character_count' => $count, 'quantity' => $enabled && $count === 3 ? 5 : 0]);
        }
        DB::table('product_clicker_prices')->insert(['product_id' => $product->id, 'character_count' => 3, 'price_rm' => 10]);

        return [$product, ...$ids];
    }

    /** @return array<string, mixed> */
    private function allocation(Product $product, int $casing, int $quantity, int $expected): array
    {
        return ['prd_code' => $product->prd_code, 'prd_name' => $product->prd_name, 'product_type' => 'clicker', 'prd_balance' => $product->prd_balance, 'agent_discount_default' => 0, 'enable_casing_stock' => 1, 'clicker_images' => ['casing' => [1 => ['id' => $casing, 'name' => 'Casing', 'stock' => array_replace(array_fill(1, 8, 0), [3 => $quantity]), 'stock_expected' => array_replace(array_fill(1, 8, 0), [3 => $expected])]]], 'casing_stock_reason' => 'Physical stock count'];
    }

    /** @param array<int, int> $quantities
     * @return array<string, mixed>
     */
    private function cart(Product $product, int $casing, int $huruf, array $quantities): array
    {
        $agent = Agent::factory()->create();
        Mail::fake();
        $this->actingAs($agent, 'agent');

        return ['idempotency_key' => (string) Str::uuid(), 'fulfilment_method' => 'delivery', 'recipient_name' => $agent->agt_name, 'phone_number' => $agent->phone_number, 'delivery_address' => '123 Test Street', 'payment_method' => 'pay_later', 'items' => array_map(fn (int $quantity): array => ['product_id' => $product->id, 'quantity' => $quantity, 'clicker_character_count' => 3, 'clicker_characters' => ['A', 'L', 'I'], 'clicker_casing_image_id' => $casing, 'clicker_huruf_image_id' => $huruf], $quantities)];
    }

    private function balance(int $id): int
    {
        return (int) DB::table('product_clicker_stocks')->where('casing_image_id', $id)->where('character_count', 3)->value('quantity');
    }
}
