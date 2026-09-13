<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Product;
use App\Support\AdminAccess;
use App\Support\ProductStockOverview;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductStockOverviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_statistics_page_search_and_stock_links_share_counts(): void
    {
        Product::factory()->create(['prd_name' => 'Target Active', 'prd_balance' => 3, 'is_visible_to_agents' => true]);
        Product::factory()->create(['prd_name' => 'Target Hidden', 'prd_balance' => 0, 'is_visible_to_agents' => false]);
        $stopped = Product::factory()->create(['prd_name' => 'Target Stopped', 'prd_balance' => 2, 'is_visible_to_agents' => false]);
        $stopped->forceFill(['discontinued_at' => now()])->save();
        Product::factory()->create(['prd_name' => 'Unrelated', 'prd_balance' => 8]);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $this->get(route('admin.products.index'))->assertOk()->assertSee('Add Product')->assertDontSee('aria-label="Statistik stok"', false);
        $response = $this->get(route('admin.products.statistics', ['search' => 'Target']));
        $response->assertOk()->assertDontSee('Add Product')->assertSee('Statistik untuk carian:')
            ->assertViewHas('stockStats', ['empty' => 0, 'critical' => 1, 'healthy' => 0, 'all' => 3, 'hidden' => 1, 'discontinued' => 1, 'discontinued_stock' => 1, 'catalogue' => 1]);
        foreach (['all' => 3, 'hidden' => 1, 'discontinued' => 1, 'discontinued-stock' => 1] as $filter => $count) {
            $url = route('admin.products.index', ['search' => 'Target', 'stock' => $filter]);
            $response->assertSee(e($url), false);
            $this->get($url)->assertOk()->assertViewHas('products', fn ($products) => $products->total() === $count);
        }
        $this->get(route('admin.products.statistics', ['search' => 'Target', 'include_hidden' => 1]))->assertOk()
            ->assertViewHas('stockStats', fn ($stats) => $stats['empty'] === 1 && $stats['all'] === 3);
        $this->get(route('admin.products.statistics', ['search' => 'NO-MATCH']))->assertOk()
            ->assertViewHas('stockStats', fn ($stats) => array_sum($stats) === 0);
        $this->get(route('admin.products.statistics', ['search' => $stopped->prd_code]))->assertOk()
            ->assertViewHas('stockStats', fn ($stats) => $stats['all'] === 1 && $stats['discontinued_stock'] === 1);
        $this->get(route('admin.products.statistics'))->assertOk()->assertViewHas('stockStats', fn ($stats) => $stats['all'] === 4);
        $this->assertSame('products.view', AdminAccess::routePermission('admin.products.statistics'));
    }

    public function test_empty_stock_shortcut_includes_hidden_products_and_updated_stock_leaves_list(): void
    {
        $empty = Product::factory()->create(['prd_balance' => 0, 'is_visible_to_agents' => false]);
        Product::factory()->create(['prd_balance' => 5, 'is_visible_to_agents' => true]);
        $stopped = Product::factory()->create(['prd_balance' => 0]);
        $stopped->forceFill(['discontinued_at' => now()])->save();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $url = route('admin.products.index', ['stock' => 'empty', 'include_hidden' => 1]);
        $this->get(route('admin.products.index'))->assertOk()->assertSee(e($url), false);
        $this->get($url)->assertOk()->assertSee('Kemas Kini Baki')
            ->assertViewHas('stockRows', fn ($rows) => $rows->total() === 1 && $rows->first()->product_id === $empty->id);
        $this->assertSame(0, $empty->fresh()->prd_balance);
        $this->patchJson(route('admin.products.balance.update', $empty), [
            'expected_balance' => 0, 'expected_quantity' => 0, 'quantity' => 7, 'reason' => 'Kiraan fizikal',
        ])->assertOk();
        $this->get($url)->assertOk()->assertViewHas('stockRows', fn ($rows) => $rows->total() === 0);
        $this->assertSame(7, $empty->fresh()->prd_balance);
        $this->assertFalse($empty->fresh()->is_visible_to_agents);
    }

    public function test_catalogue_count_matches_visible_products_and_search_regardless_of_hidden_toggle(): void
    {
        $visible = Product::factory()->create(['prd_name' => 'Target Visible', 'is_visible_to_agents' => true, 'prd_balance' => 0]);
        $remaining = Product::factory()->create(['prd_name' => 'Target Remaining', 'is_visible_to_agents' => true, 'prd_balance' => 3]);
        $remaining->forceFill(['discontinued_at' => now()])->save();
        $empty = Product::factory()->create(['prd_name' => 'Target Stopped', 'is_visible_to_agents' => true, 'prd_balance' => 0]);
        $empty->forceFill(['discontinued_at' => now()])->save();
        Product::factory()->create(['prd_name' => 'Target Hidden', 'is_visible_to_agents' => false, 'prd_balance' => 9]);
        Product::factory()->create(['prd_name' => 'Unrelated', 'is_visible_to_agents' => true, 'prd_balance' => 5]);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        foreach ([false, true] as $includeHidden) {
            $params = ['search' => 'Target', 'include_hidden' => (int) $includeHidden];
            $this->get(route('admin.products.statistics', $params))->assertOk()->assertSee('Dalam Katalog Agen')
                ->assertSee(e(route('admin.products.index', ['search' => 'Target', 'stock' => 'catalogue'])), false)
                ->assertViewHas('stockStats', fn ($stats) => $stats['catalogue'] === 2);
            $this->get(route('admin.products.index', [...$params, 'stock' => 'catalogue']))->assertOk()
                ->assertViewHas('products', fn ($products) => $products->total() === 2 && $products->pluck('id')->sort()->values()->all() === collect([$visible->id, $remaining->id])->sort()->values()->all());
        }
        $this->assertSame(3, app(ProductStockOverview::class)->statistics()['catalogue']);
        $remaining->update(['prd_balance' => 0]);
        $this->assertSame(1, app(ProductStockOverview::class)->statistics(false, 'Target')['catalogue']);
    }

    public function test_standard_thresholds_hidden_products_and_filters(): void
    {
        foreach ([0, 1, 4, 5] as $balance) {
            Product::factory()->create(['prd_balance' => $balance, 'is_visible_to_agents' => true]);
        }
        $hidden = Product::factory()->create(['prd_balance' => 0, 'is_visible_to_agents' => false]);
        $overview = app(ProductStockOverview::class);
        $this->assertSame(['empty' => 1, 'critical' => 2, 'healthy' => 1, 'all' => 5, 'hidden' => 1, 'discontinued' => 0, 'discontinued_stock' => 0, 'catalogue' => 4], $overview->statistics());
        $this->assertSame(2, $overview->statistics(true)['empty']);
        $this->assertSame([0, 1, 4], $overview->filtered('low', '', false)->pluck('balance')->map(fn ($value) => (int) $value)->all());
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $this->get(route('admin.products.index'))->assertOk()->assertSee('Stok Produk');
        $this->get(route('admin.products.index', ['stock' => 'low']))->assertOk()->assertViewHas('stockRows', fn ($rows) => $rows->total() === 3)->assertSee('Urus Stok');
        $this->get(route('admin.products.index', ['stock' => 'empty', 'include_hidden' => 1]))->assertOk()->assertViewHas('stockRows', fn ($rows) => $rows->total() === 2);
        $this->get(route('admin.products.index', ['stock' => 'hidden']))->assertOk()->assertViewHas('products', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $hidden->id);
        $this->get(route('admin.products.index', ['stock' => 'critical', 'search' => 'NOT-FOUND']))->assertOk()->assertSee('Tiada item untuk penapis ini.');
    }

    public function test_hidden_and_discontinued_counts_match_their_separate_lists(): void
    {
        $hidden = Product::factory()->create(['is_visible_to_agents' => false, 'prd_balance' => 5]);
        $active = Product::factory()->create(['is_visible_to_agents' => true, 'prd_balance' => 7]);
        $stopped = collect();
        foreach ([[false, 0], [false, 3], [true, 2]] as [$visible, $balance]) {
            $product = Product::factory()->create(['is_visible_to_agents' => $visible, 'prd_balance' => $balance]);
            $product->forceFill(['discontinued_at' => now()])->save();
            $stopped->push($product);
        }
        $before = Product::query()->orderBy('id')->get()->toArray();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        foreach ([false, true] as $includeHidden) {
            $stats = app(ProductStockOverview::class)->statistics($includeHidden);
            $this->assertSame(5, $stats['all']);
            $this->assertSame(1, $stats['hidden']);
            $this->assertSame(3, $stats['discontinued']);
            $this->assertSame(2, $stats['discontinued_stock']);
            foreach (['hidden' => [$hidden->id], 'discontinued' => $stopped->pluck('id')->all(), 'discontinued-stock' => $stopped->where('prd_balance', '>', 0)->pluck('id')->all()] as $filter => $ids) {
                $this->get(route('admin.products.index', ['stock' => $filter, 'include_hidden' => (int) $includeHidden]))
                    ->assertOk()->assertViewHas('stockFilter', $filter)
                    ->assertViewHas('products', fn ($products): bool => $products->total() === count($ids) && $products->pluck('id')->sort()->values()->all() === collect($ids)->sort()->values()->all());
            }
        }
        $this->get(route('admin.products.index', ['stock' => 'discontinued', 'search' => $active->prd_code]))
            ->assertOk()->assertViewHas('products', fn ($products): bool => $products->total() === 0);
        $this->assertSame($before, Product::query()->orderBy('id')->get()->toArray());
        $stopped[1]->forceFill(['discontinued_at' => null])->save();
        $stats = app(ProductStockOverview::class)->statistics();
        $this->assertSame(2, $stats['hidden']);
        $this->assertSame(2, $stats['discontinued']);
        $this->assertSame(1, $stats['discontinued_stock']);
    }

    public function test_clicker_counts_offered_sizes_once_and_links_to_casing(): void
    {
        $product = Product::factory()->create(['product_type' => 'clicker', 'prd_balance' => 1000]);
        $product->forceFill(['casing_stock_enabled' => true])->save();
        $casing = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => 'casing', 'position' => 1, 'alt_text' => 'Bronze', 'image_path' => 'bronze.jpg']);
        DB::table('product_clicker_images')->insert(['product_id' => $product->id, 'image_type' => 'casing', 'position' => 2, 'alt_text' => 'Not offered', 'image_path' => 'other.jpg']);
        foreach ([1, 2] as $position) {
            $huruf = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => 'huruf', 'position' => $position, 'image_path' => 'letter.jpg']);
            DB::table('product_clicker_results')->insert(['product_id' => $product->id, 'casing_image_id' => $casing, 'huruf_image_id' => $huruf, 'image_path' => 'result.jpg']);
        }
        foreach ([2, 4] as $count) {
            DB::table('product_clicker_prices')->insert(['product_id' => $product->id, 'character_count' => $count, 'price_rm' => 10]);
        }
        DB::table('product_clicker_stocks')->insert(['casing_image_id' => $casing, 'character_count' => 4, 'quantity' => 3]);
        $overview = app(ProductStockOverview::class);
        $this->assertSame(['empty' => 1, 'critical' => 1, 'healthy' => 0, 'all' => 1, 'hidden' => 0, 'discontinued' => 0, 'discontinued_stock' => 0, 'catalogue' => 1], $overview->statistics());
        $this->assertSame(2, $overview->filtered('low', 'Bronze', false)->count());
        $this->assertSame(0, $overview->filtered('low', 'Bronze', false, productSearchOnly: true)->count());
        $this->assertSame(0, $overview->statistics(false, 'Bronze')['critical']);
        $this->assertSame($overview->filtered('low', $product->prd_code, false, productSearchOnly: true)->count(), $overview->statistics(false, $product->prd_code)['empty'] + $overview->statistics(false, $product->prd_code)['critical']);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin')
            ->get(route('admin.products.index', ['stock' => 'low']))->assertOk()->assertSee('#clicker-casing-'.$casing, false)->assertDontSee('Not offered');
        $this->assertSame(1000, $product->fresh()->prd_balance);
    }
}
