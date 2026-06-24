<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => 'store',
            'address' => fake()->address(),
            'status' => 'active',
        ];
    }

    public function warehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'warehouse',
        ]);
    }
}
