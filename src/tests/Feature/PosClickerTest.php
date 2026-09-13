<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PosClickerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Agent $agent;

    private Product $product;

    private int $casing;

    private int $huruf;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = Agent::factory()->create();
        $site = BusinessSite::query()->create(['site_name' => 'Clicker site', 'city' => 'Klang', 'opened_at' => now()->subHour()]);
        $site->agents()->attach($this->agent);
        BusinessSiteOperation::query()->create(['business_site_id' => $site->id, 'opened_at' => now()->subHour()]);
        PosSession::query()->create(['agent_id' => $this->agent->id, 'business_site_id' => $site->id, 'signed_in_at' => now()->subHour()]);
        $this->product = Product::factory()->create(['product_type' => 'clicker', 'prd_balance' => 40]);
        $this->product->forceFill(['casing_stock_enabled' => true])->save();
        foreach (['casing', 'huruf'] as $type) {
            $this->$type = DB::table('product_clicker_images')->insertGetId(['product_id' => $this->product->id, 'image_type' => $type, 'position' => 1, 'alt_text' => $type, 'image_path' => $type.'.jpg']);
        }
        foreach (range(1, 8) as $count) {
            DB::table('product_clicker_stocks')->insert(['casing_image_id' => $this->casing, 'character_count' => $count, 'quantity' => 5]);
            DB::table('product_clicker_prices')->insert(['product_id' => $this->product->id, 'character_count' => $count, 'price_rm' => $count * 3, 'cost_rm' => $count]);
        }
        DB::table('product_clicker_results')->insert(['product_id' => $this->product->id, 'casing_image_id' => $this->casing, 'huruf_image_id' => $this->huruf, 'name' => 'Selected combination', 'image_path' => 'combination.jpg']);
        $this->actingAs($this->agent, 'agent');
    }

    public function test_pos_uses_size_price_cost_image_and_stock_without_consuming_other_sizes(): void
    {
        $this->get(route('agent.pos.index'))->assertOk()->assertSee('data-pos-clicker-catalog', false)->assertSee('combination.jpg')->assertSee('window.setInterval(tick, 1000)', false);
        $this->post(route('agent.pos.sales.store'), $this->data())->assertSessionHasNoErrors()->assertRedirect();
        $sale = PosSale::query()->sole();
        $item = $sale->items()->sole();
        $this->assertSame('18.00', $sale->total_amount);
        $this->assertSame('3.00', $item->unit_cost);
        $this->assertSame(['A', 'L', 'I'], $item->clicker_configuration['characters']);
        $this->assertStringEndsWith('/combination.jpg', $item->clicker_configuration['result_image']);
        $this->assertSame(3, $this->stock(3));
        $this->assertSame(5, $this->stock(4));
        $this->assertSame(38, $this->product->fresh()->prd_balance);
    }

    public function test_same_product_can_have_different_sizes_in_one_sale(): void
    {
        $data = $this->data();
        $data['items'][] = array_replace($data['items'][0], ['clicker_character_count' => 4, 'clicker_characters' => ['S', 'A', 'R', 'A']]);
        $this->post(route('agent.pos.sales.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(3, $this->stock(3));
        $this->assertSame(3, $this->stock(4));
        $this->assertSame('42.00', PosSale::query()->sole()->total_amount);
    }

    public function test_overselling_shared_size_rolls_back_entire_sale(): void
    {
        $data = $this->data();
        $data['items'][0]['quantity'] = 3;
        $data['items'][] = $data['items'][0];
        $this->post(route('agent.pos.sales.store'), $data)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('pos_sales', 0);
        $this->assertSame(5, $this->stock(3));
    }

    public function test_invalid_sizes_characters_and_foreign_images_are_rejected(): void
    {
        foreach ([0, 9] as $count) {
            $data = $this->data();
            $data['items'][0]['clicker_character_count'] = $count;
            $this->post(route('agent.pos.sales.store'), $data)->assertSessionHasErrors('items.0.clicker_character_count');
        }
        $data = $this->data();
        $data['items'][0]['clicker_characters'] = ['A'];
        $this->post(route('agent.pos.sales.store'), $data)->assertSessionHasErrors('items.0');
        $data = $this->data();
        $data['items'][0]['clicker_casing_image_id'] = $this->huruf;
        $this->post(route('agent.pos.sales.store'), $data)->assertSessionHasErrors('items.0');
        $this->assertSame(5, $this->stock(3));
    }

    public function test_correction_moves_stock_between_sizes_and_void_restores_once(): void
    {
        $this->post(route('agent.pos.sales.store'), $this->data())->assertRedirect()->assertSessionHasNoErrors();
        $sale = PosSale::query()->sole();
        $number = $sale->sale_number;
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $data = $this->correction($sale);
        $data['items'][0]['clicker_character_count'] = 4;
        $data['items'][0]['clicker_characters'] = ['S', 'A', 'R', 'A'];
        $preview = $this->post(route('admin.sales.preview', $sale), $data)->assertOk();
        $this->assertSame(3, $this->stock(3));
        $this->post(route('admin.sale-corrections.store'), ['token' => $preview->viewData('token')])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(5, $this->stock(3));
        $this->assertSame(3, $this->stock(4));
        $this->assertSame('24.00', $sale->fresh()->total_amount);
        $preview = $this->post(route('admin.sales.preview', $sale), ['action' => 'void', 'reason' => 'Wrong transaction'])->assertOk();
        $token = $preview->viewData('token');
        $this->post(route('admin.sale-corrections.store'), ['token' => $token])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('admin.sale-corrections.store'), ['token' => $token])->assertStatus(419);
        $this->assertSame(5, $this->stock(4));
        $this->assertSame(40, $this->product->fresh()->prd_balance);
        $this->assertSame($number, $sale->fresh()->sale_number);
    }

    public function test_unchanged_configuration_preserves_price_and_only_deducts_quantity_difference(): void
    {
        $this->post(route('agent.pos.sales.store'), $this->data())->assertRedirect()->assertSessionHasNoErrors();
        $sale = PosSale::query()->sole();
        DB::table('product_clicker_prices')->where('product_id', $this->product->id)->update(['price_rm' => 99, 'cost_rm' => 88]);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $data = $this->correction($sale);
        $data['items'][0]['quantity'] = 3;
        $preview = $this->post(route('admin.sales.preview', $sale), $data)->assertOk();
        $this->post(route('admin.sale-corrections.store'), ['token' => $preview->viewData('token')])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, $this->stock(3));
        $this->assertSame('9.00', $sale->items()->sole()->unit_price);
        $this->assertSame('3.00', $sale->items()->sole()->unit_cost);
    }

    public function test_one_and_eight_character_sizes_use_one_casing_per_set(): void
    {
        $data = $this->data();
        $data['items'][0]['clicker_character_count'] = 1;
        $data['items'][0]['clicker_characters'] = ['A'];
        $data['items'][] = array_replace($data['items'][0], ['clicker_character_count' => 8, 'clicker_characters' => str_split('ABCDEFGH')]);
        $this->post(route('agent.pos.sales.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(3, $this->stock(1));
        $this->assertSame(3, $this->stock(8));
        $this->assertSame(5, $this->stock(3));
    }

    public function test_stale_correction_preview_cannot_overwrite_changed_stock(): void
    {
        $this->post(route('agent.pos.sales.store'), $this->data())->assertRedirect()->assertSessionHasNoErrors();
        $sale = PosSale::query()->sole();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $data = $this->correction($sale);
        $data['items'][0]['quantity'] = 3;
        $preview = $this->post(route('admin.sales.preview', $sale), $data)->assertOk();
        DB::table('product_clicker_stocks')->where('casing_image_id', $this->casing)->where('character_count', 3)->update(['quantity' => 2]);
        $this->product->decrement('prd_balance');
        $this->post(route('admin.sale-corrections.store'), ['token' => $preview->viewData('token')])->assertRedirect()->assertSessionHasErrors('sale');
        $this->assertSame(2, $this->stock(3));
        $this->assertSame(2, $sale->items()->sole()->quantity);
    }

    public function test_void_does_not_invent_stock_for_an_untracked_historical_sale(): void
    {
        $this->product->forceFill(['casing_stock_enabled' => false])->save();
        $this->post(route('agent.pos.sales.store'), $this->data())->assertRedirect()->assertSessionHasNoErrors();
        $sale = PosSale::query()->sole();
        $this->product->forceFill(['casing_stock_enabled' => true])->save();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $preview = $this->post(route('admin.sales.preview', $sale), ['action' => 'void', 'reason' => 'Void historical transaction'])->assertOk();
        $this->post(route('admin.sale-corrections.store'), ['token' => $preview->viewData('token')])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(5, $this->stock(3));
        $this->assertSame(40, $this->product->fresh()->prd_balance);
    }

    public function test_pos_page_without_an_active_session_still_renders(): void
    {
        PosSession::query()->update(['signed_out_at' => now()]);
        $this->get(route('agent.pos.index'))->assertOk()->assertSee('window.setInterval(tick, 1000)', false);
    }

    private function data(): array
    {
        return ['sales_agent_id' => $this->agent->id, 'payment_method' => 'cash', 'items' => [['product_id' => $this->product->id, 'quantity' => 2, 'clicker_casing_image_id' => $this->casing, 'clicker_huruf_image_id' => $this->huruf, 'clicker_character_count' => 3, 'clicker_characters' => ['A', 'L', 'I']]]];
    }

    private function correction(PosSale $sale): array
    {
        return ['action' => 'correct', 'reason' => 'Correct selected size', 'sales_agent_id' => $this->agent->id, 'payment_method' => 'cash', 'sold_at' => $sale->sold_at->format('Y-m-d H:i:s'), 'items' => $sale->items->map(fn ($item) => $item->correctionInput())->all()];
    }

    private function stock(int $count): int
    {
        return (int) DB::table('product_clicker_stocks')->where('casing_image_id', $this->casing)->where('character_count', $count)->value('quantity');
    }
}
