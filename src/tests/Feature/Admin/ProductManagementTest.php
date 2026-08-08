<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Material;
use App\Models\Product;
use Database\Seeders\MaterialSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
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
        $admin = AdminUser::factory()->create();
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
        $admin = AdminUser::factory()->create();
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
        $admin = AdminUser::factory()->create();

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
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl('/products/create'))
            ->assertOk()
            ->assertSeeText('Product Type')
            ->assertSeeText('STANDARD')
            ->assertSeeText('CLICKER')
            ->assertSeeText('Casing')
            ->assertSeeText('Huruf')
            ->assertSeeText('Character Pricing')
            ->assertSeeText('Setiap character ada harga masing-masing.')
            ->assertSee('name="clicker_casing_images[]"', false)
            ->assertSee('name="clicker_huruf_images[]"', false)
            ->assertDontSee('name="clicker_character_count"', false)
            ->assertSee('name="clicker_character_prices[1]"', false)
            ->assertSee('name="clicker_character_prices[8]"', false);
    }

    public function test_product_code_must_be_unique(): void
    {
        $admin = AdminUser::factory()->create();
        Product::factory()->create(['prd_code' => 'A3D-TEST-001']);

        $this->actingAs($admin, 'admin')
            ->from($this->adminUrl('/products/create'))
            ->post($this->adminUrl('/products'), $this->validPayload())
            ->assertRedirect($this->adminUrl('/products/create'))
            ->assertSessionHasErrors('prd_code');
    }

    public function test_admin_can_update_product(): void
    {
        $admin = AdminUser::factory()->create();
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
        $admin = AdminUser::factory()->create();
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
        $admin = AdminUser::factory()->create();
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

    public function test_admin_cannot_add_a_sixth_product_picture(): void
    {
        $admin = AdminUser::factory()->create();
        $product = Product::factory()->create();

        foreach (range(1, 5) as $position) {
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
                    UploadedFile::fake()->image('sixth.jpg'),
                ],
            ]))
            ->assertRedirect($this->adminUrl("/products/{$product->id}/edit"))
            ->assertSessionHasErrors('product_images');

        $this->assertCount(5, $product->images()->get());
    }

    public function test_edit_page_displays_product_picture_manager(): void
    {
        $admin = AdminUser::factory()->create();
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

    public function test_admin_can_delete_product(): void
    {
        $admin = AdminUser::factory()->create();
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
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
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
