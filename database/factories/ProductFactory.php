<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'name' => fake()->words(3, true),
            'purchase_price' => fake()->randomFloat(2, 10, 100),
            'reseller_price' => fake()->randomFloat(2, 15, 120),
            'store_price' => fake()->randomFloat(2, 20, 150),
        ];
    }
}
