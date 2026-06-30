<?php

use App\Actions\BuildReceiptPrintData;
use App\Models\Buyer;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockEntry;
use App\Models\StockEntryItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('actions');

it('builds receipt data for a sale movement', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
    $location = Location::factory()->create();
    $product = Product::factory()->create(['name' => 'Test Product']);
    $buyer = Buyer::factory()->create();

    $sale = Sale::factory()->create([
        'location_id' => $location->id,
        'user_id' => $user->id,
        'total' => 15000,
        'paid_amount' => 20000,
        'payment_method' => 'cash',
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'qty' => 2,
        'price' => 7500,
        'subtotal' => 15000,
    ]);

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'type' => 'out',
        'quantity' => -2,
        'related_type' => Sale::class,
        'related_id' => $sale->id,
        'buyer_id' => $buyer->id,
        'notes' => 'Test sale',
    ]);

    $data = app(BuildReceiptPrintData::class)->build($movement);

    expect($data['reference'])->toBe($sale->invoice_number);
    expect($data['type'])->toBe('STOK KELUAR');
    expect($data['cashier'])->toBe($user->name);
    expect($data['location'])->toBe($location->name);
    expect($data['buyer'])->toBe($buyer->name);
    expect($data['items'])->toHaveCount(1);
    expect($data['items'][0])->toMatchArray([
        'name' => 'Test Product',
        'qty' => 2,
        'price' => 7500.0,
        'subtotal' => 15000.0,
    ]);
    expect($data['total'])->toBe(15000.0);
    expect($data['payment_method'])->toBe('Cash');
    expect($data['notes'])->toBe('Test sale');
});

it('builds receipt data for a stock transfer movement', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
    $location = Location::factory()->create();
    $product = Product::factory()->create(['name' => 'Transfer Item']);

    $transfer = StockTransfer::factory()->completed()->create();

    StockTransferItem::create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'qty' => 5,
    ]);

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'type' => 'transfer_in',
        'quantity' => 5,
        'related_type' => StockTransfer::class,
        'related_id' => $transfer->id,
    ]);

    $data = app(BuildReceiptPrintData::class)->build($movement);

    expect($data['reference'])->toBe($transfer->transfer_number);
    expect($data['type'])->toBe('TRANSFER MASUK');
    expect($data['payment_method'])->toBeNull();
    expect($data['items'])->toHaveCount(1);
    expect($data['items'][0])->toMatchArray([
        'name' => 'Transfer Item',
        'qty' => 5,
        'price' => 0,
        'subtotal' => 0,
    ]);
    expect($data['total'])->toBe(0.0);
});

it('builds receipt data for a stock entry movement', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
    $location = Location::factory()->create();
    $product = Product::factory()->create(['name' => 'Entry Item']);

    $entry = StockEntry::create([
        'type' => 'in',
        'location_id' => $location->id,
        'payment_method' => 'transfer',
        'created_by' => $user->id,
    ]);

    StockEntryItem::create([
        'stock_entry_id' => $entry->id,
        'product_id' => $product->id,
        'qty' => 10,
        'unit_price' => 2500,
    ]);

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'type' => 'in',
        'quantity' => 10,
        'related_type' => StockEntry::class,
        'related_id' => $entry->id,
    ]);

    $data = app(BuildReceiptPrintData::class)->build($movement);

    expect($data['reference'])->toBe('#SM-'.$movement->id);
    expect($data['type'])->toBe('STOK MASUK');
    expect($data['items'])->toHaveCount(1);
    expect($data['items'][0])->toMatchArray([
        'name' => 'Entry Item',
        'qty' => 10,
        'price' => 2500.0,
        'subtotal' => 25000.0,
    ]);
    expect($data['total'])->toBe(25000.0);
    expect($data['payment_method'])->toBe('Bank Transfer');
});

it('builds receipt data for fallback single product movement', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
    $location = Location::factory()->create();
    $product = Product::factory()->create(['name' => 'Fallback Item']);

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'type' => 'adjustment',
        'quantity' => -3,
        'unit_price' => 5000,
        'notes' => 'Stock opname',
    ]);

    $data = app(BuildReceiptPrintData::class)->build($movement);

    expect($data['reference'])->toBe('#SM-'.$movement->id);
    expect($data['type'])->toBe('PENYESUAIAN');
    expect($data['items'])->toHaveCount(1);
    expect($data['items'][0])->toMatchArray([
        'name' => 'Fallback Item',
        'qty' => 3,
        'price' => 5000.0,
        'subtotal' => 15000.0,
    ]);
    expect($data['total'])->toBe(15000.0);
    expect($data['notes'])->toBe('Stock opname');
});

it('handles null cashier gracefully', function () {
    $location = Location::factory()->create();
    $product = Product::factory()->create();

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'type' => 'in',
        'quantity' => 1,
    ]);

    $data = app(BuildReceiptPrintData::class)->build($movement);

    expect($data['cashier'])->toBe('-');
});

it('handles null buyer gracefully', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
    $location = Location::factory()->create();
    $product = Product::factory()->create();

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'type' => 'in',
        'quantity' => 1,
    ]);

    $data = app(BuildReceiptPrintData::class)->build($movement);

    expect($data['buyer'])->toBeNull();
});

it('uses location address and falls back to config', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
    $location = Location::factory()->create(['address' => 'Jl. Test 123']);
    $product = Product::factory()->create();

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'type' => 'in',
        'quantity' => 1,
    ]);

    $data = app(BuildReceiptPrintData::class)->build($movement);

    expect($data['store']['address'])->toBe('Jl. Test 123');
});

it('includes store config in output', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
    $location = Location::factory()->create();
    $product = Product::factory()->create();

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'type' => 'in',
        'quantity' => 1,
    ]);

    $data = app(BuildReceiptPrintData::class)->build($movement);

    expect($data['store']['name'])->toBe(config('store.name'));
    expect($data['store']['phone'])->toBe(config('store.phone'));
    expect($data['logo_url'])->toBe(asset('assets/images/logo-thermal.png'));
});

it('returns null payment method for unrelated movements', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
    $location = Location::factory()->create();
    $product = Product::factory()->create();

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'type' => 'transfer_out',
        'quantity' => -1,
    ]);

    $data = app(BuildReceiptPrintData::class)->build($movement);

    expect($data['payment_method'])->toBeNull();
});

it('handles product name fallback for sale items', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
    $location = Location::factory()->create();
    $product = Product::factory()->create();

    $sale = Sale::factory()->create([
        'location_id' => $location->id,
        'user_id' => $user->id,
        'total' => 5000,
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'qty' => 1,
        'price' => 5000,
        'subtotal' => 5000,
    ]);

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'type' => 'out',
        'quantity' => -1,
        'related_type' => Sale::class,
        'related_id' => $sale->id,
    ]);

    $data = app(BuildReceiptPrintData::class)->build($movement);

    expect($data['items'][0]['name'])->toBe($product->name);
});

it('returns correct payment method mapping for various sale payment methods', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
    $location = Location::factory()->create();
    $product = Product::factory()->create();

    $paymentMethods = [
        'cash' => 'Cash',
        'transfer' => 'Bank Transfer',
        'qris' => 'QRIS',
    ];

    foreach ($paymentMethods as $method => $expected) {
        $sale = Sale::factory()->create([
            'location_id' => $location->id,
            'user_id' => $user->id,
            'total' => 10000,
            'payment_method' => $method,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'qty' => 1,
            'price' => 10000,
            'subtotal' => 10000,
        ]);

        $movement = StockMovement::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'type' => 'out',
            'quantity' => -1,
            'related_type' => Sale::class,
            'related_id' => $sale->id,
        ]);

        $data = app(BuildReceiptPrintData::class)->build($movement);
        expect($data['payment_method'])->toBe($expected);
    }
});

it('includes additional_costs when present', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
    $location = Location::factory()->create();
    $product = Product::factory()->create();

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'type' => 'in',
        'quantity' => 1,
        'additional_costs' => [['description' => 'Ongkir', 'amount' => 5000]],
    ]);

    $data = app(BuildReceiptPrintData::class)->build($movement);

    expect($data['additional_costs'])->toBe([
        ['description' => 'Ongkir', 'amount' => 5000],
    ]);
});
