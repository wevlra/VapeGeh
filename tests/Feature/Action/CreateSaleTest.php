<?php

use App\Actions\CreateSale;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('actions');

it('creates a sale and deducts stock', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $location = Location::factory()->create();
    $product = Product::factory()->create(['selling_price' => 50000]);
    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 20,
    ]);

    $sale = app(CreateSale::class)->execute(
        user: $user,
        locationId: $location->id,
        items: [['product_id' => $product->id, 'qty' => 3]],
        paymentMethod: 'cash',
        paidAmount: 150000,
    );

    expect($sale)->toBeInstanceOf(Sale::class)
        ->and($sale->payment_method)->toBe('cash');

    $stock = Stock::where('product_id', $product->id)
        ->where('location_id', $location->id)
        ->first();
    expect($stock->qty)->toBe(17);

    $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id]);
    $this->assertDatabaseHas('stock_movements', [
        'related_type' => 'App\\Models\\Sale',
        'related_id' => $sale->id,
        'type' => 'out',
    ]);
});

it('throws DomainException when stock is insufficient', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $location = Location::factory()->create();
    $product = Product::factory()->create(['selling_price' => 50000]);
    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 2,
    ]);

    app(CreateSale::class)->execute(
        user: $user,
        locationId: $location->id,
        items: [['product_id' => $product->id, 'qty' => 5]],
    );
})->throws(DomainException::class, 'Insufficient stock');

it('throws DomainException when sale total is zero', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $location = Location::factory()->create();
    $product = Product::factory()->create(['selling_price' => 0]);
    $product->prices()->delete();
    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 10,
    ]);

    app(CreateSale::class)->execute(
        user: $user,
        locationId: $location->id,
        items: [['product_id' => $product->id, 'qty' => 1]],
    );
})->throws(DomainException::class, 'Sale total must be greater than zero');
