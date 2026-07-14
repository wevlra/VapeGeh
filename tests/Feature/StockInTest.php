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
