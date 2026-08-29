<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StockOpname>
 */
class StockOpnameFactory extends Factory
{
    public function definition(): array
    {
        return [
            'opname_number' => 'SO-'.strtoupper(Str::random(8)),
            'location_id' => Location::factory(),
            'created_by' => User::factory()->admin(),
            'status' => 'draft',
            'notes' => $this->faker->sentence(),
            'completed_at' => null,
        ];
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
