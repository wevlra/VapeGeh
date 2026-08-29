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
            'price_1' => fake()->randomFloat(2, 20, 200),
            'reseller_price' => fake()->randomFloat(2, 15, 150),
            'price_2' => fake()->randomFloat(2, 25, 250),
            'status' => 'active',
        ];
    }
}
