<?php

use App\Actions\UpdateSale;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('actions');

it('reconciles stock when sale items are changed', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $location = Location::factory()->create();
    $product = Product::factory()->create();

    // Stock starts at 8 (already had 2 deducted by the original sale)
    $stock = Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 8,
    ]);

    $sale = Sale::factory()->create([
        'location_id' => $location->id,
        'user_id' => $user->id,
        'total' => 100000,
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'qty' => 2,
        'price' => 50000,
        'subtotal' => 100000,
    ]);

    StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'related_type' => Sale::class,
        'related_id' => $sale->id,
        'type' => 'out',
        'quantity' => -2,
        'created_by' => $user->id,
    ]);

    app(UpdateSale::class)->execute($sale, [
        'items' => [['product_id' => $product->id, 'qty' => 4, 'price' => 50000]],
        'payment_method' => 'transfer',
        'paid_amount' => 200000,
    ]);

    $stock->refresh();
    // UpdateSale reverses old: 8 + 2 = 10. Deducts new: 10 - 4 = 6
    expect($stock->qty)->toBe(6);

    $sale->refresh();
    expect($sale->total)->toBe(200000.0)
        ->and($sale->payment_method)->toBe('transfer');
});

it('throws DomainException when new stock is insufficient', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $location = Location::factory()->create();
    $product = Product::factory()->create();

    $stock = Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 3,
    ]);

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

    StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'related_type' => Sale::class,
        'related_id' => $sale->id,
        'type' => 'out',
        'quantity' => -1,
        'created_by' => $user->id,
    ]);

    // After reversal: 3 + 1 = 4. New request: 5. Should fail.
    app(UpdateSale::class)->execute($sale, [
        'items' => [['product_id' => $product->id, 'qty' => 5, 'price' => 50000]],
    ]);
})->throws(DomainException::class, 'Insufficient stock');
