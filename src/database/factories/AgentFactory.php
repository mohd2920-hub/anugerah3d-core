<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'login_id' => 'AGT'.$this->faker->unique()->numberBetween(10001, 99999),
            'agt_name' => $this->faker->name(),
            'id_number' => (string) $this->faker->unique()->numerify('######-##-####'),
            'email' => $this->faker->unique()->safeEmail(),
            'phone_number' => '01'.$this->faker->numberBetween(10000000, 99999999),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'agt_status' => Agent::StatusActive,
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->randomElement(['Johor', 'Kedah', 'Kelantan', 'Selangor', 'Kuala Lumpur']),
            'discount_percentage' => $this->faker->randomFloat(1, 5, 30),
            'profile_picture' => null,
            'last_login_at' => null,
            'last_login_ip' => null,
            'total_sale' => $this->faker->randomFloat(2, 0, 5000),
        ];
    }
}
