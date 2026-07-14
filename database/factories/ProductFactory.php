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
            'name' => fake()->words(3, true),
            'purchase_price' => fake()->randomFloat(2, 10, 100),
            'selling_price' => fake()->randomFloat(2, 20, 200),
        ];
    }
}
