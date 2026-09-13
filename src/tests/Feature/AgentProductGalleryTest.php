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

    public function test_dashboard_excludes_hidden_products_and_reflects_visibility_changes(): void
    {
        $agent = Agent::factory()->create();
        $visible = Product::factory()->create(['is_visible_to_agents' => true]);
        $hidden = Product::factory()->create(['is_visible_to_agents' => false, 'prd_balance' => 9999]);

        $this->actingAs($agent, 'agent')
            ->get('http://'.config('domains.agent').'/dashboard')
            ->assertOk()
            ->assertSeeText($visible->prd_name)
            ->assertDontSeeText($hidden->prd_name)
            ->assertViewHas('catalogueProducts', fn ($products) => $products->total() === 1)
            ->assertViewHas('topProducts', fn ($products) => $products->modelKeys() === [$visible->id]);

        $hidden->update(['is_visible_to_agents' => true]);
        $this->get('http://'.config('domains.agent').'/dashboard')
            ->assertOk()
            ->assertSeeText($hidden->prd_name)
            ->assertViewHas('catalogueProducts', fn ($products) => $products->total() === 2);
    }

    public function test_catalogue_pagination_and_search_exclude_hidden_products(): void
    {
        $agent = Agent::factory()->create();
        Product::factory()->count(13)->create(['is_visible_to_agents' => true]);
        $hidden = Product::factory()->create([
            'is_visible_to_agents' => false,
            'prd_name' => 'Hidden Catalogue Item',
            'prd_code' => 'HIDDEN-CATALOGUE-CODE',
            'prd_balance' => 9999,
        ]);
        $this->actingAs($agent, 'agent');

        foreach ([1 => 12, 2 => 1] as $page => $count) {
            $response = $this->getJson(route('agent.dashboard.products', ['page' => $page]))
                ->assertOk()
                ->assertJsonPath('total', 13)
                ->assertJsonPath('count', $count);
            $this->assertStringNotContainsString($hidden->prd_code, $response->json('html'));
        }

        foreach ([$hidden->prd_name, $hidden->prd_code] as $search) {
            $this->getJson(route('agent.dashboard.products', ['search' => $search]))
                ->assertOk()
                ->assertJsonPath('total', 0)
                ->assertJsonPath('count', 0)
                ->assertJsonPath('has_more', false);
        }
    }

    public function test_product_images_are_ordered_and_limited_to_ten_positions(): void
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
            'position' => 11,
        ]);
    }

    public function test_agent_dashboard_renders_up_to_ten_gallery_images(): void
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
            ->assertSee('data-catalogue-gallery', false)
            ->assertSee('data-gallery-images=', false)
            ->assertSeeText('Gallery Product')
            ->assertSee('aria-label="Open picture 10 of 10 for Gallery Product"', false)
            ->assertDontSee('aria-label="Open picture 11', false)
            ->assertSee('gallery-1.jpg')
            ->assertSee('gallery-10.jpg')
            ->assertDontSee('legacy.jpg');
    }

    public function test_agent_gallery_includes_product_video(): void
    {
        $agent = Agent::factory()->create();
        $product = Product::factory()->create();
        $product->forceFill(['video_path' => 'videos/products/demo.mp4'])->save();

        $this->actingAs($agent, 'agent')
            ->get('http://'.config('domains.agent').'/dashboard')
            ->assertOk()
            ->assertSee('demo.mp4')
            ->assertSeeText('Play video');
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
