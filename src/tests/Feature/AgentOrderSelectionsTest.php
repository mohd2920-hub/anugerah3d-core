<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentOrderSelectionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_checkout_preserves_different_selections_of_the_same_product(): void
    {
        [$product, $payload] = $this->prepareCart(2);

        $this->postJson(route('agent.orders.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('order.total', '18.00');

        $items = Order::query()->sole()->items()->orderBy('id')->get();
        $this->assertCount(2, $items);
        $this->assertSame([$product->id, $product->id], $items->pluck('product_id')->all());
        $this->assertSame([['J', 'O', 'E'], ['M', 'U', 'H', 'A']], $items->pluck('clicker_characters')->all());
        $this->assertSame([3, 4], $items->pluck('clicker_character_count')->all());
        $this->assertSame(['6.75', '8.25'], $items->pluck('line_total')->all());
        $this->assertSame([1, 1], $items->pluck('reserved_quantity')->all());
        $this->assertSame(['casing-1.jpg', 'casing-2.jpg'], $items->pluck('clicker_casing_image_path')->all());
        $this->assertSame(['huruf-1.jpg', 'huruf-2.jpg'], $items->pluck('clicker_huruf_image_path')->all());
        $this->assertSame(0, $product->fresh()->prd_balance);

        $this->postJson(route('agent.orders.store'), $payload)->assertCreated();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertSame(0, $product->fresh()->prd_balance);
    }

    public function test_checkout_rejects_selections_exceeding_combined_stock(): void
    {
        [$product, $payload] = $this->prepareCart(1);

        $this->postJson(route('agent.orders.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(1, $product->fresh()->prd_balance);
    }

    private function prepareCart(int $stock): array
    {
        $agent = Agent::factory()->create(['discount_percentage' => 25]);
        $product = Product::factory()->create([
            'product_type' => 'clicker',
            'is_visible_to_agents' => true,
            'prd_balance' => $stock,
        ]);
        Mail::fake();
        $this->actingAs($agent, 'agent');
        $items = [];

        foreach (['JOE', 'MUHA'] as $index => $name) {
            $count = strlen($name);
            DB::table('product_clicker_prices')->insert([
                'product_id' => $product->id,
                'character_count' => $count,
                'price_rm' => $index === 0 ? 9 : 11,
            ]);
            $item = [
                'product_id' => $product->id,
                'quantity' => 1,
                'clicker_character_count' => $count,
                'clicker_characters' => str_split($name),
            ];

            foreach (['casing', 'huruf'] as $type) {
                $item["clicker_{$type}_image_id"] = DB::table('product_clicker_images')->insertGetId([
                    'product_id' => $product->id,
                    'image_type' => $type,
                    'image_path' => $type.'-'.($index + 1).'.jpg',
                    'position' => $index + 1,
                ]);
            }

            $items[] = $item;
        }

        return [$product, [
            'idempotency_key' => (string) Str::uuid(),
            'fulfilment_method' => 'delivery',
            'recipient_name' => $agent->agt_name,
            'phone_number' => $agent->phone_number,
            'delivery_address' => '123 Test Street',
            'payment_method' => 'pay_later',
            'items' => $items,
        ]];
    }
}
