<?php

use App\Actions\DeleteSale;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('actions');

it('reverses stock and deletes sale with related records', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $location = Location::factory()->create();
    $product = Product::factory()->create();
    $stock = Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 5,
    ]);

    $sale = Sale::factory()->create([
        'location_id' => $location->id,
        'user_id' => $user->id,
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'qty' => 3,
        'price' => 50000,
        'subtotal' => 150000,
    ]);

    StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'related_type' => Sale::class,
        'related_id' => $sale->id,
        'type' => 'out',
        'quantity' => -3,
        'created_by' => $user->id,
    ]);

    app(DeleteSale::class)->execute($sale);

    $stock->refresh();
    expect($stock->qty)->toBe(8);

    $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    $this->assertDatabaseMissing('sale_items', ['sale_id' => $sale->id]);
    $this->assertDatabaseMissing('stock_movements', [
        'related_type' => 'App\\Models\\Sale',
        'related_id' => $sale->id,
    ]);
});

it('throws DomainException when stock movement is missing', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $location = Location::factory()->create();
    $product = Product::factory()->create();

    $sale = Sale::factory()->create([
        'location_id' => $location->id,
        'user_id' => $user->id,
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'qty' => 1,
        'price' => 50000,
        'subtotal' => 50000,
    ]);

    app(DeleteSale::class)->execute($sale);
})->throws(DomainException::class, 'Stock movement not found');
