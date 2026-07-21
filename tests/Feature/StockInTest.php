<?php

use App\Filament\Admin\Pages\StockIn;
use App\Models\Expense;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can perform stock in and set custom selling price', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $location = Location::factory()->create();
    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();

    Livewire::test(StockIn::class)
        ->fillForm([
            'vendor_id' => $vendor->id,
            'location_id' => $location->id,
        ])
        ->set('items', [
            [
                'product_id' => $product->id,
                'qty' => 10,
                'price' => 50000,
            ],
        ])
        ->call('save');

    $stock = Stock::where('product_id', $product->id)
        ->where('location_id', $location->id)
        ->first();

    expect($stock)->not->toBeNull()
        ->and($stock->qty)->toBe(10);

    $expense = Expense::where('location_id', $location->id)->first();
    expect($expense)->not->toBeNull()
        ->and((float) $expense->amount)->toBe(500000.00)
        ->and($expense->category)->toBe('purchase');
});

it('calculates simple average purchase price when new stock is added with different price', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $location = Location::factory()->create();
    $vendor = Vendor::factory()->create();

    // Product has an initial purchase price of 50000
    $product = Product::factory()->create([
        'purchase_price' => 50000,
    ]);

    // Perform stock in with new price of 60000 (different from 50000)
    Livewire::test(StockIn::class)
        ->fillForm([
            'vendor_id' => $vendor->id,
            'location_id' => $location->id,
        ])
        ->set('items', [
            [
                'product_id' => $product->id,
                'qty' => 10,
                'price' => 60000,
            ],
        ])
        ->call('save');

    // Fresh instance from DB
    $product->refresh();

    // Average price should be (50000 + 60000) / 2 = 55000
    expect((float) $product->purchase_price)->toBe(55000.00);
});

it('uses new stock price directly when product has no prior purchase price', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $location = Location::factory()->create();
    $vendor = Vendor::factory()->create();

    // Product has an initial purchase price of 0
    $product = Product::factory()->create([
        'purchase_price' => 0,
    ]);

    // Perform stock in with price of 60000
    Livewire::test(StockIn::class)
        ->fillForm([
            'vendor_id' => $vendor->id,
            'location_id' => $location->id,
        ])
        ->set('items', [
            [
                'product_id' => $product->id,
                'qty' => 10,
                'price' => 60000,
            ],
        ])
        ->call('save');

    // Fresh instance from DB
    $product->refresh();

    // Price should be 60000, not (0 + 60000) / 2 = 30000
    expect((float) $product->purchase_price)->toBe(60000.00);
});
