<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AgentProductGalleryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_product_images_are_ordered_and_limited_to_five_positions(): void
    {
        $product = Product::factory()->create();

        ProductImage::factory()->for($product)->create([
            'image_path' => 'images/products/third.jpg',
            'position' => 3,
        ]);
        ProductImage::factory()->for($product)->create([
            'image_path' => 'images/products/first.jpg',
            'position' => 1,
        ]);

        $this->assertSame(
            [1, 3],
            $product->images()->pluck('position')->all(),
        );

        $this->expectException(ValidationException::class);

        ProductImage::factory()->for($product)->create([
            'position' => 6,
        ]);
    }

    public function test_agent_dashboard_renders_up_to_five_gallery_images(): void
    {
        $agent = Agent::factory()->create();
        $product = Product::factory()->create([
            'prd_code' => 'GALLERY-001',
            'prd_name' => 'Gallery Product',
            'prd_picture' => 'images/products/legacy.jpg',
        ]);

        foreach (range(1, ProductImage::MAX_IMAGES_PER_PRODUCT) as $position) {
            ProductImage::factory()->for($product)->create([
                'image_path' => "images/products/gallery-{$position}.jpg",
                'alt_text' => "Gallery view {$position}",
                'position' => $position,
            ]);
        }

        $this->actingAs($agent, 'agent')
            ->get('http://'.config('domains.agent').'/dashboard')
            ->assertOk()
            ->assertViewIs('agent.dashboard')
            ->assertSee('data-product-gallery', false)
            ->assertSee('data-images=', false)
            ->assertSee('gallery-1.jpg')
            ->assertSee('gallery-5.jpg')
            ->assertDontSee('legacy.jpg');
    }

    public function test_agent_dashboard_uses_legacy_picture_when_gallery_is_empty(): void
    {
        $agent = Agent::factory()->create();
        Product::factory()->create([
            'prd_code' => 'LEGACY-001',
            'prd_name' => 'Legacy Product',
            'prd_picture' => 'images/products/legacy-only.jpg',
        ]);

        $this->actingAs($agent, 'agent')
            ->get('http://'.config('domains.agent').'/dashboard')
            ->assertOk()
            ->assertSee('legacy-only.jpg');
    }
}
