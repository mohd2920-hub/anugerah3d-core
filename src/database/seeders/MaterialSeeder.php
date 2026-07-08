<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = [
            ['name' => 'PLA', 'description' => null],
            ['name' => 'PLA SILK', 'description' => null],
            ['name' => 'PLA MATTE', 'description' => null],
            ['name' => 'PLA RAINBOW', 'description' => null],
            ['name' => 'PLA TRANSPARENT', 'description' => null],
            ['name' => 'PETG', 'description' => null],
            ['name' => 'PETG MATTE', 'description' => null],
            ['name' => 'PETG TRANSPARENT', 'description' => null],
            ['name' => 'PETG STARRY', 'description' => null],
            ['name' => 'ABS', 'description' => null],
            ['name' => 'TPU', 'description' => null],
        ];

        foreach ($materials as $material) {
            Material::query()->updateOrCreate(
                ['name' => $material['name']],
                $material,
            );
        }
    }
}
