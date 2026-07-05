<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

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
        ]);

        $this->actingAs($admin, 'admin')
            ->get($this->adminUrl('/products'))
            ->assertOk()
            ->assertViewIs('admin.products.index')
            ->assertSeeText('Products')
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

    public function test_admin_can_delete_product(): void
    {
        $admin = AdminUser::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin, 'admin')
            ->delete($this->adminUrl("/products/{$product->id}"))
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
            'weight_g' => 18.5,
            'width_mm' => 60,
            'height_mm' => 28,
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
}
