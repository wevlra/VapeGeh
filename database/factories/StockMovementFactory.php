<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
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
            'type' => $type = fake()->randomElement(['in', 'out']),
            'quantity' => $type === 'in'
                ? fake()->numberBetween(1, 10)
                : fake()->numberBetween(-10, -1),
            'unit_price' => null,
            'related_type' => null,
            'related_id' => null,
            'buyer_id' => null,
            'additional_costs' => null,
            'notes' => null,
        ];
    }
}
