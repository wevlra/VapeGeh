<?php

use App\Filament\Admin\Pages\StockIn;
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
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 10,
                    'price' => 50000,
                    'selling_price_type' => 'custom',
                    'custom_selling_price' => 65000,
                ],
            ],
        ])
        ->call('save');

    $stock = Stock::where('product_id', $product->id)
        ->where('location_id', $location->id)
        ->first();

    expect($stock)->not->toBeNull()
        ->and($stock->qty)->toBe(10)
        ->and($stock->price)->toBe(65000.00);
});
