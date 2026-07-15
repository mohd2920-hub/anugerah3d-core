<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::query()
            ->whereNotNull('prd_picture')
            ->eachById(function (Product $product): void {
                ProductImage::query()->updateOrCreate(
                    [
                        'product_id' => $product->getKey(),
                        'position' => 1,
                    ],
                    [
                        'image_path' => $product->prd_picture,
                        'alt_text' => $product->prd_name.' main view',
                    ],
                );
            });

        $sampleProduct = Product::query()
            ->where('prd_code', 'A3D-KC-001')
            ->first();

        if (! $sampleProduct) {
            return;
        }

        $sampleImages = [
            2 => ['images/products/sample-keychain-side.png', 'Custom name keychain side view'],
            3 => ['images/products/sample-keychain-detail.png', 'Custom name keychain material detail'],
            4 => ['images/products/sample-keychain-scale.png', 'Custom name keychain shown at scale'],
            5 => ['images/products/sample-keychain-back.png', 'Custom name keychain back view'],
        ];

        foreach ($sampleImages as $position => [$imagePath, $altText]) {
            ProductImage::query()->updateOrCreate(
                [
                    'product_id' => $sampleProduct->getKey(),
                    'position' => $position,
                ],
                [
                    'image_path' => $imagePath,
                    'alt_text' => $altText,
                ],
            );
        }
    }
}
