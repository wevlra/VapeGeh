<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'location_id' => Location::factory(),
            'total' => fake()->randomFloat(2, 10000, 500000),
            'paid_amount' => fake()->randomFloat(2, 10000, 500000),
            'payment_method' => fake()->randomElement(['cash', 'transfer', 'qris', 'other']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
