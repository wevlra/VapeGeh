<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->lexify('???').fake()->numerify('###')),
            'name' => fake()->words(3, true),
            'unit' => fake()->randomElement(['pcs', 'bottle', 'pack']),
            'purchase_price' => fake()->randomFloat(2, 10, 100),
            'selling_price' => fake()->randomFloat(2, 15, 150),
            'status' => 'active',
        ];
    }
}
