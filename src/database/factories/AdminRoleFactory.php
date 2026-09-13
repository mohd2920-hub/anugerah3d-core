<?php

namespace Database\Factories;

use App\Models\AdminRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdminRole> */
class AdminRoleFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->words(3, true), 'description' => null, 'permissions' => []];
    }
}
