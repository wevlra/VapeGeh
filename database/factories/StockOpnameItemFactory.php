<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOpnameItem>
 */
class StockOpnameItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stock_opname_id' => StockOpname::factory(),
            'product_id' => Product::factory(),
            'system_qty' => 10,
            'actual_qty' => 10,
            'difference' => 0,
            'notes' => null,
        ];
    }
}
