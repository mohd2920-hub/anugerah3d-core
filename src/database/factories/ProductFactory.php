<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productTypes = [
            ['code' => 'HY', 'names' => ['Hyurf', 'Hybrid']],
            ['code' => 'KC', 'names' => ['Bola', 'Bal Kecil', 'Ball']],
            ['code' => 'LM', 'names' => ['Lampu Daud', 'Lamp', 'Lighting']],
            ['code' => 'KY', 'names' => ['Keychain', 'Gantungan Kunci', 'Key Ring']],
            ['code' => 'CB', 'names' => ['Custom Box', 'Kotak Kustom', 'Box']],
            ['code' => 'PL', 'names' => ['Plakat', 'Nameplate', 'Signage']],
            ['code' => 'MP', 'names' => ['Mouse Pad', 'Pad', 'Mat']],
            ['code' => 'MG', 'names' => ['Magnet', 'Magnet Custom', 'Magnetic']],
            ['code' => 'PK', 'names' => ['Pen Kustom', 'Custom Pen', 'Pen']],
            ['code' => 'TG', 'names' => ['Tag', 'Label', 'Sticker']],
        ];

        $type = $this->faker->randomElement($productTypes);
        $sequence = $this->faker->unique()->numberBetween(1001, 9999);
        $name = $this->faker->randomElement($type['names']);

        return [
            'prd_code' => $type['code'].'_'.$sequence,
            'prd_name' => $name.' '.$this->faker->colorName(),
            'weight_g' => $this->faker->numberBetween(5, 500),
            'width_mm' => $this->faker->numberBetween(5, 200),
            'height_mm' => $this->faker->numberBetween(5, 200),
            'prd_balance' => $this->faker->numberBetween(10, 1000),
            'cost_rm' => $this->faker->randomFloat(2, 1, 100),
            'price_selling' => $this->faker->randomFloat(2, 5, 500),
            'agent_discount_default' => $this->faker->randomFloat(2, 5, 30),
            'prd_picture' => 'https://via.placeholder.com/300x300?text='.$this->faker->word(),
        ];
    }
}
