<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'location_id' => Location::factory(),
            'type' => fake()->randomElement(['in', 'out', 'adjustment']),
            'quantity' => fake()->numberBetween(1, 100),
            'unit_price' => fake()->randomFloat(2, 1000, 50000),
            'created_by' => User::factory(),
        ];
    }
}
