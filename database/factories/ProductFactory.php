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
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            $product->prices()->create([
                'label' => 'Store',
                'price' => $product->purchase_price * fake()->randomFloat(2, 1.5, 2.5),
            ]);
            $product->prices()->create([
                'label' => 'Reseller',
                'price' => $product->purchase_price * fake()->randomFloat(2, 1.2, 1.8),
            ]);
        });
    }
}
