<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'location_id' => Location::factory(),
            'qty' => fake()->numberBetween(0, 100),
        ];
    }
}
