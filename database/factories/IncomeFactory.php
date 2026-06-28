<?php

namespace Database\Factories;

use App\Models\Income;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'category' => fake()->randomElement(['sale', 'debt_payment', 'other']),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'date' => fake()->date(),
            'created_by' => User::factory()->admin(),
        ];
    }
}
