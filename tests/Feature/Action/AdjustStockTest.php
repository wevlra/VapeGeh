<?php

use App\Actions\AdjustStock;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('actions');

it('increases stock quantity and creates a stock movement', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $product = Product::factory()->create();
    $stock = Stock::factory()->create(['product_id' => $product->id, 'qty' => 10]);

    app(AdjustStock::class)->execute($stock, 15, 'Restocked');

    $stock->refresh();
    expect($stock->qty)->toBe(15);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'adjustment',
        'quantity' => 5,
        'notes' => 'Restocked',
        'created_by' => $user->id,
    ]);
});

it('decreases stock quantity and creates a negative movement', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $product = Product::factory()->create();
    $stock = Stock::factory()->create(['product_id' => $product->id, 'qty' => 20]);

    app(AdjustStock::class)->execute($stock, 12);

    $stock->refresh();
    expect($stock->qty)->toBe(12);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'adjustment',
        'quantity' => -8,
    ]);
});

it('does nothing when quantity is unchanged', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $product = Product::factory()->create();
    $stock = Stock::factory()->create(['product_id' => $product->id, 'qty' => 10]);

    app(AdjustStock::class)->execute($stock, 10);

    $stock->refresh();
    expect($stock->qty)->toBe(10);

    $this->assertDatabaseMissing('stock_movements', [
        'product_id' => $product->id,
        'type' => 'adjustment',
    ]);
});

it('throws DomainException when new quantity is negative', function () {
    $product = Product::factory()->create();
    $stock = Stock::factory()->create(['product_id' => $product->id, 'qty' => 10]);

    app(AdjustStock::class)->execute($stock, -1);
})->throws(DomainException::class, 'Stock quantity cannot be negative.');
