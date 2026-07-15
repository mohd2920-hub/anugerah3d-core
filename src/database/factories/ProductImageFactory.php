<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'image_path' => 'https://placehold.co/900x700/e2e8f0/17324d.png?text='.fake()->word(),
            'alt_text' => fake()->sentence(4),
            'position' => fake()->numberBetween(1, ProductImage::MAX_IMAGES_PER_PRODUCT),
        ];
    }
}
