<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materialIds = Material::query()->pluck('id', 'name');

        $templates = [
            [
                'code' => 'KC',
                'name' => 'Custom Name Keychain',
                'weight_g' => 18,
                'width_mm' => 60,
                'height_mm' => 28,
                'length_mm' => 100,
                'colors' => ['Blue', 'Red', 'Yellow', 'White'],
                'material' => 'PLA',
                'balance' => 120,
                'cost_rm' => 2.40,
                'price_selling' => 8.00,
                'discount' => 15,
                'bg' => 'dbeafe',
                'fg' => '1d4ed8',
            ],
            [
                'code' => 'GF',
                'name' => 'Corporate Gift Tag',
                'weight_g' => 24,
                'width_mm' => 75,
                'height_mm' => 42,
                'length_mm' => 80,
                'colors' => ['White', 'Black', 'Silver', 'Gold'],
                'material' => 'PETG',
                'balance' => 80,
                'cost_rm' => 3.20,
                'price_selling' => 12.00,
                'discount' => 12,
                'bg' => 'f8fafc',
                'fg' => '334155',
            ],
            [
                'code' => 'MN',
                'name' => 'Miniature Display Stand',
                'weight_g' => 95,
                'width_mm' => 90,
                'height_mm' => 110,
                'length_mm' => 120,
                'colors' => ['Black', 'Grey', 'White', 'Navy'],
                'material' => 'PLA MATTE',
                'balance' => 35,
                'cost_rm' => 14.50,
                'price_selling' => 38.00,
                'discount' => 10,
                'bg' => 'e2e8f0',
                'fg' => '0f172a',
            ],
            [
                'code' => 'PL',
                'name' => 'Desktop Name Plate',
                'weight_g' => 72,
                'width_mm' => 160,
                'height_mm' => 55,
                'length_mm' => 12,
                'colors' => ['Walnut', 'Black', 'White', 'Gold'],
                'material' => 'PLA SILK',
                'balance' => 46,
                'cost_rm' => 8.70,
                'price_selling' => 29.00,
                'discount' => 14,
                'bg' => 'fef3c7',
                'fg' => '92400e',
            ],
            [
                'code' => 'LT',
                'name' => 'LED Lithophane Panel',
                'weight_g' => 130,
                'width_mm' => 140,
                'height_mm' => 180,
                'length_mm' => 18,
                'colors' => ['Warm White', 'White', 'Amber', 'Cream'],
                'material' => 'PLA TRANSPARENT',
                'balance' => 28,
                'cost_rm' => 22.00,
                'price_selling' => 69.00,
                'discount' => 8,
                'bg' => 'fff7ed',
                'fg' => 'c2410c',
            ],
            [
                'code' => 'OR',
                'name' => 'Organizer Tray',
                'weight_g' => 155,
                'width_mm' => 180,
                'height_mm' => 120,
                'length_mm' => 35,
                'colors' => ['Green', 'Black', 'Grey', 'Blue'],
                'material' => 'PETG MATTE',
                'balance' => 55,
                'cost_rm' => 18.30,
                'price_selling' => 49.00,
                'discount' => 11,
                'bg' => 'dcfce7',
                'fg' => '166534',
            ],
            [
                'code' => 'MG',
                'name' => 'Fridge Magnet Set',
                'weight_g' => 32,
                'width_mm' => 50,
                'height_mm' => 50,
                'length_mm' => 6,
                'colors' => ['Rainbow', 'Pink', 'Cyan', 'Purple'],
                'material' => 'PLA RAINBOW',
                'balance' => 210,
                'cost_rm' => 4.10,
                'price_selling' => 16.00,
                'discount' => 18,
                'bg' => 'fce7f3',
                'fg' => 'be185d',
            ],
            [
                'code' => 'SP',
                'name' => 'Spare Part Bracket',
                'weight_g' => 48,
                'width_mm' => 85,
                'height_mm' => 35,
                'length_mm' => 22,
                'colors' => ['Black', 'Natural', 'Grey', 'Red'],
                'material' => 'ABS',
                'balance' => 67,
                'cost_rm' => 6.80,
                'price_selling' => 24.00,
                'discount' => 9,
                'bg' => 'fee2e2',
                'fg' => '991b1b',
            ],
            [
                'code' => 'ST',
                'name' => 'Phone Stand',
                'weight_g' => 64,
                'width_mm' => 72,
                'height_mm' => 115,
                'length_mm' => 82,
                'colors' => ['Blue', 'Black', 'White', 'Orange'],
                'material' => 'PLA MATTE',
                'balance' => 98,
                'cost_rm' => 7.20,
                'price_selling' => 25.00,
                'discount' => 13,
                'bg' => 'e0f2fe',
                'fg' => '075985',
            ],
            [
                'code' => 'TP',
                'name' => 'Flexible Cable Clip',
                'weight_g' => 12,
                'width_mm' => 28,
                'height_mm' => 18,
                'length_mm' => 18,
                'colors' => ['Black', 'Clear', 'White', 'Blue'],
                'material' => 'TPU',
                'balance' => 320,
                'cost_rm' => 1.20,
                'price_selling' => 5.00,
                'discount' => 20,
                'bg' => 'ede9fe',
                'fg' => '5b21b6',
            ],
        ];

        for ($number = 1; $number <= 100; $number++) {
            $template = $templates[($number - 1) % count($templates)];
            $batch = intdiv($number - 1, count($templates)) + 1;
            $variant = ($number - 1) % 5;
            $sequence = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $color = $template['colors'][($number - 1) % count($template['colors'])];
            $productCode = sprintf('A3D-%s-%s', $template['code'], $sequence);

            $product = [
                'prd_code' => $productCode,
                'prd_name' => $template['name'].($batch > 1 ? ' Batch '.str_pad((string) $batch, 2, '0', STR_PAD_LEFT) : ''),
                'weight_g' => $template['weight_g'] + ($variant * 1.5),
                'width_mm' => $template['width_mm'] + ($variant * 2),
                'height_mm' => $template['height_mm'] + ($variant * 2),
                'length_mm' => $template['length_mm'] + $variant,
                'color' => $color,
                'material' => $template['material'],
                'material_id' => $materialIds[$template['material']] ?? null,
                'prd_balance' => max(0, $template['balance'] - ($batch * 3) + ($variant * 4)),
                'cost_rm' => $template['cost_rm'] + ($batch * 0.35) + ($variant * 0.20),
                'price_selling' => $template['price_selling'] + ($batch * 1.25) + ($variant * 0.50),
                'agent_discount_default' => $template['discount'],
                'prd_picture' => sprintf(
                    'https://placehold.co/160x160/%s/%s.png?text=%s',
                    $template['bg'],
                    $template['fg'],
                    rawurlencode($template['code'].' '.$sequence),
                ),
            ];

            Product::query()->updateOrCreate(
                ['prd_code' => $product['prd_code']],
                $product,
            );
        }
    }
}
