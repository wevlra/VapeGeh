<?php

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('has the correct product columns', function () {
    expect(Schema::hasColumns('products', [
        'name', 'purchase_price',
    ]))->toBeTrue();
});

it('has the correct stock columns and unique constraint', function () {
    expect(Schema::hasColumns('stocks', [
        'product_id', 'location_id', 'qty',
    ]))->toBeTrue();

    $indexes = Schema::getIndexes('stocks');
    $uniqueProductLocation = collect($indexes)->contains(function ($index) {
        return $index['unique']
            && is_array($index['columns'])
            && in_array('product_id', $index['columns'])
            && in_array('location_id', $index['columns']);
    });
    expect($uniqueProductLocation)->toBeTrue();
});

it('has the correct stock movement columns', function () {
    expect(Schema::hasColumns('stock_movements', [
        'product_id', 'location_id', 'type', 'quantity',
        'related_type', 'related_id', 'notes', 'created_by',
    ]))->toBeTrue();
});

it('creates a product with factory', function () {
    $product = Product::factory()->create();

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->name)->not->toBeEmpty();
});

it('creates stock for a product at a location', function () {
    $stock = Stock::factory()->create([
        'qty' => 50,
    ]);

    expect($stock->product)->toBeInstanceOf(Product::class)
        ->and($stock->location)->toBeInstanceOf(Location::class)
        ->and($stock->qty)->toBe(50);
});

it('relates stock to product and location', function () {
    $product = Product::factory()->create();
    $location = Location::factory()->create();

    $stock = Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
    ]);

    expect($stock->product->is($product))->toBeTrue()
        ->and($stock->location->is($location))->toBeTrue();
});

it('computes total asset per location and globally', function () {
    $warehouse = Location::factory()->warehouse()->create();
    $store = Location::factory()->create();
    $product1 = Product::factory()->create(['selling_price' => 100000]);
    $product2 = Product::factory()->create(['selling_price' => 80000]);

    Stock::create(['product_id' => $product1->id, 'location_id' => $warehouse->id, 'qty' => 5]);
    Stock::create(['product_id' => $product2->id, 'location_id' => $store->id, 'qty' => 3]);

    expect((float) $warehouse->total_asset)->toBe(500000.0)
        ->and((float) $store->total_asset)->toBe(240000.0)
        ->and(Location::getTotalAssetOfAll())->toBe(740000.0);
});
