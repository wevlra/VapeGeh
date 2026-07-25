<?php

use App\Filament\Admin\Pages\StockOut;
use App\Models\Income;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can perform stock out and record income', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $location = Location::factory()->create();
    $product = Product::factory()->create([
        'price_1' => 150000.00,
    ]);

    // Seed stock
    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 20,
    ]);

    Livewire::test(StockOut::class)
        ->fillForm([
            'location_id' => $location->id,
        ])
        ->set('items', [
            [
                'product_id' => $product->id,
                'qty' => 5,
                'price' => 150000.00,
            ],
        ])
        ->call('save');

    // Assert stock decreased
    $stock = Stock::where('product_id', $product->id)
        ->where('location_id', $location->id)
        ->first();

    expect($stock->qty)->toBe(15);

    // Assert Income was created
    $income = Income::where('location_id', $location->id)->first();
    expect($income)->not->toBeNull()
        ->and((float) $income->amount)->toBe(750000.00) // 5 * 150000
        ->and($income->category)->toBe('sale');
});

it('includes additional costs in stock entry total amount for stock out history', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $location = Location::factory()->create();
    $product = Product::factory()->create(['price_1' => 100000.00]);

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 10,
    ]);

    Livewire::test(StockOut::class)
        ->fillForm([
            'location_id' => $location->id,
            'additional_costs' => [
                ['description' => 'Ongkir', 'amount' => 20000.00],
            ],
        ])
        ->set('items', [
            [
                'product_id' => $product->id,
                'qty' => 2,
                'price' => 100000.00,
            ],
        ])
        ->call('save');

    $stockEntry = StockEntry::where('type', 'out')->first();
    expect($stockEntry->total_amount)->toBe(220000.00); // 200000 items + 20000 additional cost
});
