<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\CustomerOrder;
use App\Models\Material;
use App\Models\Order;
use App\Models\Product;
use Database\Seeders\MaterialSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure materials are seeded before each test
        $this->seed(MaterialSeeder::class);
        // Verify materials were created
        $this->assertGreaterThan(0, Material::count());
    }

    public function test_products_index_requires_authentication(): void
    {
        $this->get($this->adminUrl('/products'))
            ->assertRedirect('/login');
    }

    public function test_admin_can_view_products_index(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create([
            'prd_code' => 'A3D-TEST-001',
            'prd_name' => 'Desk Name Plate',
            'product_type' => 'standard',
        ]);

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl('/products'))
            ->assertOk()
            ->assertViewIs('admin.products.index')
            ->assertSeeText('Products')
            ->assertSeeText('Add Product')
            ->assertSee('href="'.route('admin.products.create').'"', false)
            ->assertSeeText($product->prd_code)
            ->assertSeeText($product->prd_name);
    }

    public function test_admin_can_search_products(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        Product::factory()->create([
            'prd_code' => 'A3D-KEY-001',
            'prd_name' => 'Keychain Batch',
        ]);
        Product::factory()->create([
            'prd_code' => 'A3D-MUG-002',
            'prd_name' => 'Printed Mug',
        ]);

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl('/products?search=KEY'))
            ->assertOk()
            ->assertSeeText('A3D-KEY-001')
            ->assertDontSeeText('A3D-MUG-002');
    }

    public function test_admin_can_create_product(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')
            ->post($this->adminUrl('/products'), $this->validPayload())
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'prd_code' => 'A3D-TEST-001',
            'prd_name' => 'Desk Name Plate',
            'prd_balance' => 25,
        ]);
    }

    public function test_create_page_displays_clicker_ui_controls(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl('/products/create'))
            ->assertOk()
            ->assertSeeText('Product Type')
            ->assertSeeText('STANDARD')
            ->assertSeeText('CLICKER')
            ->assertSeeText('Casing')
            ->assertSeeText('Huruf')
            ->assertSeeText('Character Pricing')
            ->assertSeeText('Pricing (RM)')
            ->assertSeeText('Cost (RM)')
            ->assertSeeText('Weight (g)')
            ->assertSeeText('Width (mm)')
            ->assertSeeText('Height (mm)')
            ->assertSeeText('Length (mm)')
            ->assertSee('name="clicker_images[casing][1][name]"', false)
            ->assertSee('name="clicker_images[huruf][1][name]"', false)
            ->assertDontSee('name="clicker_character_count"', false)
            ->assertSee('name="clicker_character_prices[1][price_rm]"', false)
            ->assertSee('name="clicker_character_prices[8][length_mm]"', false);
    }

    public function test_admin_can_create_clicker_product_with_character_prices(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $characterPrices = $this->validClickerCharacterPrices();

        $this->actingAs($admin, 'admin')
            ->post($this->adminUrl('/products'), $this->validPayload([
                'product_type' => 'clicker',
                'clicker_character_prices' => $characterPrices,
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('prd_code', 'A3D-TEST-001')->firstOrFail();

        $this->assertSame('clicker', $product->product_type);

        foreach ($characterPrices as $characterCount => $pricing) {
            $this->assertDatabaseHas('product_clicker_prices', [
                'product_id' => $product->getKey(),
                'character_count' => (int) $characterCount,
                'price_rm' => number_format((float) $pricing['price_rm'], 2, '.', ''),
            ]);
        }
    }

    public function test_edit_page_displays_clicker_character_pricing_for_clicker_product(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create([
            'product_type' => 'clicker',
        ]);

        $prices = [];

        foreach ($this->validClickerCharacterPrices() as $characterCount => $pricing) {
            $prices[] = [
                'product_id' => $product->getKey(),
                'character_count' => (int) $characterCount,
                'price_rm' => number_format((float) $pricing['price_rm'], 2, '.', ''),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('product_clicker_prices')->insert($prices);

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl("/products/{$product->id}/edit"))
            ->assertOk()
            ->assertSeeText('Character Pricing')
            ->assertSee('name="clicker_character_prices[1][price_rm]"', false)
            ->assertSee('name="clicker_character_prices[8][length_mm]"', false)
            ->assertSee('value="12.00"', false)
            ->assertSee('value="33.00"', false);
    }

    public function test_admin_can_update_clicker_product_character_prices(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create([
            'product_type' => 'clicker',
        ]);

        $existing = [];

        foreach ($this->validClickerCharacterPrices() as $characterCount => $pricing) {
            $existing[] = [
                'product_id' => $product->getKey(),
                'character_count' => (int) $characterCount,
                'price_rm' => number_format((float) $pricing['price_rm'], 2, '.', ''),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('product_clicker_prices')->insert($existing);

        $updatedPrices = $this->validClickerCharacterPrices([
            1 => ['price_rm' => 18.00, 'cost_rm' => 8.00],
            8 => ['price_rm' => 44.00, 'weight_g' => 28.00],
        ]);

        $this->actingAs($admin, 'admin')
            ->put($this->adminUrl("/products/{$product->id}"), $this->validPayload([
                'prd_code' => $product->prd_code,
                'product_type' => 'clicker',
                'clicker_character_prices' => $updatedPrices,
            ]))
            ->assertRedirect(route('admin.products.index'));

        foreach ($updatedPrices as $characterCount => $pricing) {
            $this->assertDatabaseHas('product_clicker_prices', [
                'product_id' => $product->getKey(),
                'character_count' => (int) $characterCount,
                'price_rm' => number_format((float) $pricing['price_rm'], 2, '.', ''),
            ]);
        }
    }

    public function test_product_code_must_be_unique(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        Product::factory()->create(['prd_code' => 'A3D-TEST-001']);

        $this->actingAs($admin, 'admin')
            ->from($this->adminUrl('/products/create'))
            ->post($this->adminUrl('/products'), $this->validPayload())
            ->assertRedirect($this->adminUrl('/products/create'))
            ->assertSessionHasErrors('prd_code');
    }

    public function test_admin_can_update_product(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create([
            'prd_code' => 'A3D-OLD-001',
            'prd_name' => 'Old Product',
        ]);

        $this->actingAs($admin, 'admin')
            ->put($this->adminUrl("/products/{$product->id}"), $this->validPayload([
                'prd_code' => 'A3D-NEW-002',
                'prd_name' => 'Updated Product',
                'prd_balance' => 40,
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'prd_code' => 'A3D-NEW-002',
            'prd_name' => 'Updated Product',
            'prd_balance' => 40,
        ]);
    }

    public function test_admin_can_create_product_with_multiple_pictures_and_choose_main(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = null;

        try {
            $this->actingAs($admin, 'admin')
                ->post($this->adminUrl('/products'), $this->validPayload([
                    'product_images' => [
                        UploadedFile::fake()->image('front.jpg', 600, 400),
                        UploadedFile::fake()->image('side.png', 400, 600),
                    ],
                    'main_image' => 'new-1',
                ]))
                ->assertRedirect(route('admin.products.index'));

            $product = Product::query()->where('prd_code', 'A3D-TEST-001')->firstOrFail();
            $images = $product->images()->get();

            $this->assertCount(2, $images);
            $this->assertSame('Desk Name Plate view 2', $images->first()->alt_text);
            $this->assertSame($images->first()->image_path, $product->fresh()->prd_picture);
            $this->assertFileExists(public_path($images->first()->image_path));
        } finally {
            $this->deleteManagedProductFiles($product);
        }
    }

    public function test_admin_can_remove_a_picture_and_choose_new_upload_as_main(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create([
            'prd_picture' => 'https://example.com/front.jpg',
        ]);
        $front = $product->images()->create([
            'image_path' => 'https://example.com/front.jpg',
            'alt_text' => 'Front',
            'position' => 1,
        ]);
        $product->images()->create([
            'image_path' => 'https://example.com/back.jpg',
            'alt_text' => 'Back',
            'position' => 2,
        ]);

        try {
            $this->actingAs($admin, 'admin')
                ->put($this->adminUrl("/products/{$product->id}"), $this->validPayload([
                    'prd_code' => $product->prd_code,
                    'remove_image_ids' => [$front->getKey()],
                    'product_images' => [
                        UploadedFile::fake()->image('detail.webp', 500, 500),
                    ],
                    'main_image' => 'new-0',
                ]))
                ->assertRedirect(route('admin.products.index'));

            $product->refresh();
            $images = $product->images()->get();

            $this->assertCount(2, $images);
            $this->assertStringStartsWith('images/products/product-', $images->first()->image_path);
            $this->assertSame($images->first()->image_path, $product->prd_picture);
            $this->assertSame('https://example.com/back.jpg', $images->last()->image_path);
            $this->assertFalse($images->contains('image_path', 'https://example.com/front.jpg'));
        } finally {
            $this->deleteManagedProductFiles($product);
        }
    }

    public function test_admin_can_upload_keep_replace_and_remove_one_product_video(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $paths = [];

        try {
            foreach (['first', 'replacement'] as $name) {
                $this->actingAs($admin, 'admin')
                    ->put($this->adminUrl("/products/{$product->id}"), $this->validPayload([
                        'prd_code' => $product->prd_code,
                        'product_video' => UploadedFile::fake()->create($name.'.mp4', 100, 'video/mp4'),
                    ]))
                    ->assertSessionHasNoErrors()
                    ->assertRedirect(route('admin.products.index'));
                $paths[] = $product->fresh()->video_path;
                $this->assertFileExists(public_path(end($paths)));

                $this->actingAs($admin, 'admin')
                    ->put($this->adminUrl("/products/{$product->id}"), $this->validPayload(['prd_code' => $product->prd_code]))
                    ->assertSessionHasNoErrors();
                $this->assertSame(end($paths), $product->fresh()->video_path);
            }
            $this->assertNotSame($paths[0], $paths[1]);
            $this->actingAs($admin, 'admin')
                ->put($this->adminUrl("/products/{$product->id}"), $this->validPayload([
                    'prd_code' => $product->prd_code,
                    'remove_product_video' => '1',
                ]))
                ->assertSessionHasNoErrors();
            $this->assertNull($product->fresh()->video_path);
        } finally {
            foreach ($paths as $path) {
                File::delete(public_path($path));
            }
        }
    }

    public function test_invalid_or_oversized_video_preserves_current_video(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $product->forceFill(['video_path' => 'videos/products/original.mp4'])->save();

        foreach ([
            UploadedFile::fake()->image('picture.jpg'),
            UploadedFile::fake()->create('large.mp4', 20481, 'video/mp4'),
            UploadedFile::fake()->create('script.php', 10, 'video/mp4'),
        ] as $upload) {
            $this->actingAs($admin, 'admin')
                ->put($this->adminUrl("/products/{$product->id}"), $this->validPayload([
                    'prd_code' => $product->prd_code,
                    'product_video' => $upload,
                ]))
                ->assertSessionHasErrors('product_video');
            $this->assertSame('videos/products/original.mp4', $product->fresh()->video_path);
        }
    }

    public function test_admin_can_add_pictures_one_at_a_time_up_to_ten(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        try {
            foreach (range(1, 10) as $number) {
                $main = $product->images()->first();
                $previousPaths = $product->images()->pluck('image_path')->all();

                $this->actingAs($admin, 'admin')
                    ->put($this->adminUrl("/products/{$product->id}"), $this->validPayload([
                        'prd_code' => $product->prd_code,
                        'product_images' => [UploadedFile::fake()->image("view-{$number}.jpg")],
                        'main_image' => $main ? 'existing-'.$main->getKey() : 'new-0',
                    ]))
                    ->assertSessionHasNoErrors()
                    ->assertRedirect(route('admin.products.index'));

                $images = $product->images()->get();
                $this->assertCount($number, $images);
                foreach ($previousPaths as $path) {
                    $this->assertTrue($images->contains('image_path', $path));
                }
                if ($main) {
                    $this->assertSame($main->image_path, $product->fresh()->prd_picture);
                }
            }
        } finally {
            $this->deleteManagedProductFiles($product);
        }
    }

    public function test_admin_cannot_add_an_eleventh_product_picture(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        foreach (range(1, 10) as $position) {
            $product->images()->create([
                'image_path' => "https://example.com/view-{$position}.jpg",
                'alt_text' => "View {$position}",
                'position' => $position,
            ]);
        }

        $this->actingAs($admin, 'admin')
            ->from($this->adminUrl("/products/{$product->id}/edit"))
            ->put($this->adminUrl("/products/{$product->id}"), $this->validPayload([
                'prd_code' => $product->prd_code,
                'product_images' => [
                    UploadedFile::fake()->image('eleventh.jpg'),
                ],
            ]))
            ->assertRedirect($this->adminUrl("/products/{$product->id}/edit"))
            ->assertSessionHasErrors('product_images');

        $this->assertCount(10, $product->images()->get());
    }

    public function test_edit_page_displays_product_picture_manager(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $product->images()->create([
            'image_path' => 'https://example.com/main.jpg',
            'alt_text' => 'Main view',
            'position' => 1,
        ]);

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl("/products/{$product->id}/edit"))
            ->assertOk()
            ->assertSeeText('Product pictures')
            ->assertSee('name="product_images[]"', false)
            ->assertSee('name="main_image"', false)
            ->assertSee('name="remove_image_ids[]"', false)
            ->assertSeeText('Main');
    }

    public function test_products_with_agent_or_customer_order_history_cannot_be_deleted(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $agent = Agent::factory()->create();
        $this->actingAs($admin, 'admin');

        foreach ([Order::class, CustomerOrder::class] as $orderClass) {
            foreach (['pending', 'cancelled'] as $status) {
                $product = Product::factory()->create();
                $order = new $orderClass;
                $order->forceFill([
                    'agent_id' => $agent->id, 'idempotency_key' => (string) Str::uuid(),
                    ...($orderClass === CustomerOrder::class ? ['tracking_token' => (string) Str::uuid()] : []),
                    'status' => $status, 'fulfilment_method' => 'delivery', 'recipient_name' => 'Test Customer',
                    'phone_number' => '0123456789', 'payment_method' => 'pay_later',
                    'subtotal' => 10, 'total_amount' => 10, 'total_units' => 1, 'placed_at' => now(),
                ])->save();
                $item = $order->items()->create([
                    'product_id' => $product->id, 'product_code' => $product->prd_code, 'product_name' => $product->prd_name,
                    'quantity' => 1, 'unit_selling_price' => 10, 'discount_percentage' => 0, 'unit_price' => 10, 'line_total' => 10,
                ]);

                $this->followingRedirects()->delete($this->adminUrl("/products/{$product->id}"), ['delete_password' => 'password'])
                    ->assertOk()->assertSee('tidak boleh dipadam')->assertSee('Hentikan Produk');
                $this->assertModelExists($product);
                $this->assertModelExists($order);
                $this->assertModelExists($item);
            }
        }
    }

    public function test_admin_can_delete_product(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin, 'admin')
            ->delete($this->adminUrl("/products/{$product->id}"), [
                'delete_password' => 'password',
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    /**
     * @param  array<int, array<string, float|string>>  $overrides
     * @return array<int, array<string, float|string>>
     */
    private function validClickerCharacterPrices(array $overrides = []): array
    {
        $pricing = [];

        foreach (range(1, 8) as $characterCount) {
            $pricing[$characterCount] = [
                'price_rm' => number_format(9 + ($characterCount * 3), 2, '.', ''),
                'cost_rm' => number_format(4 + ($characterCount * 2), 2, '.', ''),
                'weight_g' => 10 + $characterCount,
                'width_mm' => 20 + $characterCount,
                'height_mm' => 30 + $characterCount,
                'length_mm' => 40 + $characterCount,
            ];
        }

        foreach ($overrides as $characterCount => $values) {
            $pricing[$characterCount] = array_replace($pricing[$characterCount], $values);
        }

        return $pricing;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function test_clicker_slots_can_be_cleared_without_removing_other_slots(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create(['product_type' => 'clicker', 'casing_stock_enabled' => true, 'prd_balance' => 0]);
        $casing = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => 'casing', 'position' => 1, 'alt_text' => 'Casing', 'image_path' => 'images/products/history-casing.jpg']);
        $huruf = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => 'huruf', 'position' => 1, 'alt_text' => 'Huruf', 'image_path' => 'images/products/history-huruf.jpg']);
        $other = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => 'huruf', 'position' => 2, 'alt_text' => 'Other', 'image_path' => 'images/products/other.jpg']);
        DB::table('product_clicker_stocks')->insert(['casing_image_id' => $casing, 'character_count' => 1, 'quantity' => 0]);
        $payload = $this->validPayload(['prd_code' => $product->prd_code, 'product_type' => 'clicker', 'clicker_images' => [
            'casing' => [1 => ['id' => $casing, 'remove' => 1, 'stock' => array_fill(1, 8, 0), 'stock_expected' => array_fill(1, 8, 0)]],
            'huruf' => [1 => ['id' => $huruf, 'remove' => 1]],
        ]]);

        $this->actingAs($admin, 'admin')->get(route('admin.products.edit', $product))->assertOk()->assertSee('data-slot-clear', false)->assertSee('data-slot-undo', false);
        $this->put(route('admin.products.update', $product), $payload)->assertSessionHasNoErrors()->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseMissing('product_clicker_images', ['id' => $casing]);
        $this->assertDatabaseMissing('product_clicker_images', ['id' => $huruf]);
        $this->assertDatabaseMissing('product_clicker_stocks', ['casing_image_id' => $casing]);
        $this->assertDatabaseHas('product_clicker_images', ['id' => $other, 'position' => 2]);
        $this->get(route('admin.products.edit', $product))->assertOk()
            ->assertSee('id="clicker-casing-new-1"', false)->assertSee('id="clicker-huruf-new-1"', false);
    }

    public function test_clicker_slot_removal_rejects_stock_and_foreign_images(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create(['product_type' => 'clicker']);
        $casing = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => 'casing', 'position' => 1, 'image_path' => 'test.jpg']);
        DB::table('product_clicker_stocks')->insert(['casing_image_id' => $casing, 'character_count' => 1, 'quantity' => 3]);
        $payload = $this->validPayload(['prd_code' => $product->prd_code, 'product_type' => 'clicker', 'clicker_images' => ['casing' => [1 => ['id' => $casing, 'remove' => 1]]]]);
        $this->actingAs($admin, 'admin')->put(route('admin.products.update', $product), $payload)->assertSessionHasErrors('clicker_images.casing.1.remove');
        $this->assertDatabaseHas('product_clicker_stocks', ['casing_image_id' => $casing, 'quantity' => 3]);
        $other = Product::factory()->create(['product_type' => 'clicker']);
        $payload['prd_code'] = $other->prd_code;
        $this->put(route('admin.products.update', $other), $payload)->assertSessionHasErrors('clicker_images.casing.1.id');
        $this->assertDatabaseHas('product_clicker_images', ['id' => $casing]);
    }

    public function test_clicker_slot_removal_preserves_order_history(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $product = Product::factory()->create(['product_type' => 'clicker']);
        $casing = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => 'casing', 'position' => 1, 'image_path' => 'test.jpg']);
        $order = new Order;
        $order->forceFill(['agent_id' => Agent::factory()->create()->id, 'idempotency_key' => (string) Str::uuid(), 'status' => 'completed', 'fulfilment_method' => 'delivery', 'recipient_name' => 'Test', 'phone_number' => '0123456789', 'payment_method' => 'pay_later', 'subtotal' => 10, 'total_amount' => 10, 'total_units' => 1, 'placed_at' => now()])->save();
        $item = $order->items()->create(['product_id' => $product->id, 'product_code' => $product->prd_code, 'product_name' => $product->prd_name, 'quantity' => 1, 'unit_selling_price' => 10, 'discount_percentage' => 0, 'unit_price' => 10, 'line_total' => 10, 'clicker_casing_image_id' => $casing]);
        $this->actingAs($admin, 'admin')->put(route('admin.products.update', $product), $this->validPayload(['prd_code' => $product->prd_code, 'product_type' => 'clicker', 'clicker_images' => ['casing' => [1 => ['id' => $casing, 'remove' => 1]]]]))->assertSessionHasErrors('clicker_images.casing.1.remove');
        $this->assertModelExists($item);
        $this->assertDatabaseHas('product_clicker_images', ['id' => $casing]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'prd_code' => 'A3D-TEST-001',
            'prd_name' => 'Desk Name Plate',
            'product_type' => 'standard',
            'weight_g' => 18.5,
            'width_mm' => 60,
            'height_mm' => 28,
            'length_mm' => 100,
            'color' => 'Blue',
            'material_id' => Material::query()->value('id'),
            'prd_balance' => 25,
            'cost_rm' => 2.50,
            'price_selling' => 9.90,
            'agent_discount_default' => 15,
            'prd_picture' => null,
        ], $overrides);
    }

    private function adminUrl(string $path): string
    {
        return 'http://'.config('domains.admin').$path;
    }

    private function deleteManagedProductFiles(?Product $product): void
    {
        if (! $product) {
            return;
        }

        $product->images()
            ->pluck('image_path')
            ->filter(fn (string $path): bool => str_starts_with($path, 'images/products/product-'))
            ->each(fn (string $path) => File::delete(public_path($path)));
    }
}
