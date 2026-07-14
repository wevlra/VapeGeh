<?php

use App\Actions\CompleteStockOpname;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('actions');

it('completes opname, adjusts stocks, and creates movements when difference exists', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $location = Location::factory()->create();
    $product1 = Product::factory()->create(['purchase_price' => 10000]);
    $product2 = Product::factory()->create(['purchase_price' => 20000]);

    // Product 1: decrease stock (loss: 2 * 10000 = 20000)
    Stock::factory()->create([
        'product_id' => $product1->id,
        'location_id' => $location->id,
        'qty' => 10,
    ]);

    // Product 2: increase stock (gain: 2 * 20000 = 40000)
    Stock::factory()->create([
        'product_id' => $product2->id,
        'location_id' => $location->id,
        'qty' => 5,
    ]);

    // Net value difference: +20000 (gain/income)

    $opname = StockOpname::factory()->create([
        'location_id' => $location->id,
        'status' => 'draft',
        'created_by' => $admin->id,
    ]);

    StockOpnameItem::factory()->create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product1->id,
        'system_qty' => 10,
        'actual_qty' => 8, // missing 2
        'difference' => -2,
    ]);

    StockOpnameItem::factory()->create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product2->id,
        'system_qty' => 5,
        'actual_qty' => 7, // excess 2
        'difference' => 2,
    ]);

    app(CompleteStockOpname::class)->execute($opname);

    $opname->refresh();
    expect($opname->status)->toBe('completed');
    expect($opname->completed_at)->not->toBeNull();

    // Assert stocks updated
    $stock1 = Stock::where('product_id', $product1->id)->where('location_id', $location->id)->first();
    expect($stock1->qty)->toBe(8);

    $stock2 = Stock::where('product_id', $product2->id)->where('location_id', $location->id)->first();
    expect($stock2->qty)->toBe(7);

    // Assert movements created (2 movements)
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product1->id,
        'location_id' => $location->id,
        'type' => 'opname',
        'quantity' => -2,
        'related_type' => StockOpname::class,
        'related_id' => $opname->id,
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product2->id,
        'location_id' => $location->id,
        'type' => 'opname',
        'quantity' => 2,
        'related_type' => StockOpname::class,
        'related_id' => $opname->id,
    ]);

    // Assert Income was created (net positive)
    $this->assertDatabaseHas('incomes', [
        'location_id' => $location->id,
        'category' => 'opname',
        'amount' => 20000,
    ]);
});

it('creates expense when net difference is negative', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $location = Location::factory()->create();
    $product = Product::factory()->create(['purchase_price' => 15000]);

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 10,
    ]);

    $opname = StockOpname::factory()->create([
        'location_id' => $location->id,
        'status' => 'draft',
        'created_by' => $admin->id,
    ]);

    StockOpnameItem::factory()->create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product->id,
        'system_qty' => 10,
        'actual_qty' => 7, // missing 3 (loss: 3 * 15000 = 45000)
        'difference' => -3,
    ]);

    app(CompleteStockOpname::class)->execute($opname);

    // Assert Expense was created
    $this->assertDatabaseHas('expenses', [
        'location_id' => $location->id,
        'category' => 'opname',
        'amount' => 45000,
    ]);
});

it('skips products with no difference', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $location = Location::factory()->create();
    $product = Product::factory()->create();

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 10,
    ]);

    $opname = StockOpname::factory()->create([
        'location_id' => $location->id,
        'status' => 'draft',
        'created_by' => $admin->id,
    ]);

    StockOpnameItem::factory()->create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product->id,
        'system_qty' => 10,
        'actual_qty' => 10,
        'difference' => 0,
    ]);

    app(CompleteStockOpname::class)->execute($opname);

    // Assert movement is NOT created since difference is 0
    $this->assertDatabaseMissing('stock_movements', [
        'product_id' => $product->id,
        'related_type' => StockOpname::class,
    ]);
});

it('throws DomainException when opname is already completed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $opname = StockOpname::factory()->completed()->create([
        'created_by' => $admin->id,
    ]);

    app(CompleteStockOpname::class)->execute($opname);
})->throws(DomainException::class, 'sudah selesai');
