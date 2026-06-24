<?php

use App\Actions\CreateSale;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('has the correct sales columns', function () {
    expect(Schema::hasColumns('sales', [
        'invoice_number', 'user_id', 'location_id', 'total',
        'paid_amount', 'payment_method', 'notes',
    ]))->toBeTrue();
});

it('has the correct sale_items columns', function () {
    expect(Schema::hasColumns('sale_items', [
        'sale_id', 'product_id', 'qty', 'price', 'subtotal',
    ]))->toBeTrue();
});

it('creates a sale with items and deducts stock', function () {
    $location = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);
    $product = Product::factory()->create(['selling_price' => 25000]);

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 100,
    ]);

    $action = app(CreateSale::class);
    $sale = $action->execute(
        user: $staff,
        locationId: $location->id,
        items: [
            ['product_id' => $product->id, 'qty' => 3],
        ],
        paymentMethod: 'cash',
        paidAmount: 75000,
    );

    expect($sale)->toBeInstanceOf(Sale::class)
        ->and($sale->invoice_number)->toStartWith('INV-')
        ->and($sale->total)->toBe('75000.00')
        ->and($sale->items)->toHaveCount(1);

    $item = $sale->items->first();
    expect($item->qty)->toBe(3)
        ->and($item->price)->toBe('25000.00')
        ->and($item->subtotal)->toBe('75000.00');

    $stock = Stock::where('product_id', $product->id)
        ->where('location_id', $location->id)
        ->first();
    expect($stock->qty)->toBe(97);

    $movement = StockMovement::where('product_id', $product->id)
        ->where('type', 'out')
        ->first();
    expect($movement->quantity)->toBe(-3)
        ->and($movement->location_id)->toBe($location->id);
});

it('throws exception when stock is insufficient for sale', function () {
    $location = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);
    $product = Product::factory()->create(['selling_price' => 10000]);

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 2,
    ]);

    $action = app(CreateSale::class);

    expect(fn () => $action->execute(
        user: $staff,
        locationId: $location->id,
        items: [
            ['product_id' => $product->id, 'qty' => 5],
        ],
        paymentMethod: 'cash',
        paidAmount: 50000,
    ))->toThrow(DomainException::class);
});

it('generates an invoice number automatically', function () {
    $location = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);
    $product = Product::factory()->create(['selling_price' => 10000]);

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 50,
    ]);

    $action = app(CreateSale::class);
    $sale1 = $action->execute(
        user: $staff,
        locationId: $location->id,
        items: [['product_id' => $product->id, 'qty' => 1]],
        paymentMethod: 'cash',
        paidAmount: 10000,
    );
    $sale2 = $action->execute(
        user: $staff,
        locationId: $location->id,
        items: [['product_id' => $product->id, 'qty' => 1]],
        paymentMethod: 'cash',
        paidAmount: 10000,
    );

    expect($sale1->invoice_number)->not->toBe($sale2->invoice_number)
        ->and($sale1->invoice_number)->toStartWith('INV-')
        ->and($sale2->invoice_number)->toStartWith('INV-');
});

it('relates sale to user and location', function () {
    $location = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);
    $product = Product::factory()->create(['selling_price' => 10000]);

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 50,
    ]);

    $action = app(CreateSale::class);
    $sale = $action->execute(
        user: $staff,
        locationId: $location->id,
        items: [['product_id' => $product->id, 'qty' => 1]],
        paymentMethod: 'cash',
        paidAmount: 10000,
    );

    expect($sale->user->is($staff))->toBeTrue()
        ->and($sale->location->is($location))->toBeTrue();
});
