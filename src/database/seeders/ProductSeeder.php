<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'prd_code' => 'A3D-KC-001',
                'prd_name' => 'Custom Name Keychain',
                'weight_g' => 18,
                'width_mm' => 60,
                'height_mm' => 28,
                'prd_balance' => 120,
                'cost_rm' => 2.40,
                'price_selling' => 8.00,
                'agent_discount_default' => 15,
                'prd_picture' => null,
            ],
            [
                'prd_code' => 'A3D-GF-002',
                'prd_name' => 'Corporate Gift Tag',
                'weight_g' => 24,
                'width_mm' => 75,
                'height_mm' => 42,
                'prd_balance' => 80,
                'cost_rm' => 3.20,
                'price_selling' => 12.00,
                'agent_discount_default' => 12,
                'prd_picture' => null,
            ],
            [
                'prd_code' => 'A3D-MN-003',
                'prd_name' => 'Miniature Display Stand',
                'weight_g' => 95,
                'width_mm' => 90,
                'height_mm' => 110,
                'prd_balance' => 35,
                'cost_rm' => 14.50,
                'price_selling' => 38.00,
                'agent_discount_default' => 10,
                'prd_picture' => null,
            ],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['prd_code' => $product['prd_code']],
                $product,
            );
        }
    }
}
