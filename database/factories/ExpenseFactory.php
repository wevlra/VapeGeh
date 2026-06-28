<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'category' => fake()->randomElement(['purchase', 'salary', 'utilities', 'transport', 'other']),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'date' => fake()->date(),
            'created_by' => User::factory()->admin(),
        ];
    }
}
