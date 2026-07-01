<?php

use App\Actions\CompleteStockTransfer;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('has the correct stock_transfers columns', function () {
    expect(Schema::hasColumns('stock_transfers', [
        'transfer_number', 'from_location_id', 'to_location_id',
        'status', 'notes', 'completed_at', 'created_by',
    ]))->toBeTrue();
});

it('has the correct stock_transfer_items columns', function () {
    expect(Schema::hasColumns('stock_transfer_items', [
        'stock_transfer_id', 'product_id', 'qty',
    ]))->toBeTrue();
});

it('creates a stock transfer with items', function () {
    $warehouse = Location::factory()->warehouse()->create();
    $store = Location::factory()->create();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $product = Product::factory()->create();

    $transfer = StockTransfer::factory()->create([
        'from_location_id' => $warehouse->id,
        'to_location_id' => $store->id,
        'created_by' => $admin->id,
    ]);

    $item = StockTransferItem::create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'qty' => 10,
    ]);

    expect($transfer->fromLocation->is($warehouse))->toBeTrue()
        ->and($transfer->toLocation->is($store))->toBeTrue()
        ->and($transfer->creator->is($admin))->toBeTrue()
        ->and($transfer->items)->toHaveCount(1)
        ->and($item->product->is($product))->toBeTrue();
});

it('completes a transfer and moves stock correctly', function () {
    $warehouse = Location::factory()->warehouse()->create();
    $store = Location::factory()->create();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $product = Product::factory()->create();

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $warehouse->id,
        'qty' => 50,
    ]);

    $transfer = StockTransfer::factory()->create([
        'from_location_id' => $warehouse->id,
        'to_location_id' => $store->id,
        'status' => 'pending',
        'created_by' => $admin->id,
    ]);

    StockTransferItem::create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'qty' => 20,
    ]);

    $action = app(CompleteStockTransfer::class);
    $action->execute($transfer, $admin);

    $transfer->refresh();

    expect($transfer->status)->toBe('completed')
        ->and($transfer->completed_at)->not->toBeNull();

    $warehouseStock = Stock::where('product_id', $product->id)
        ->where('location_id', $warehouse->id)
        ->first();
    expect($warehouseStock->qty)->toBe(30);

    $storeStock = Stock::where('product_id', $product->id)
        ->where('location_id', $store->id)
        ->first();
    expect($storeStock->qty)->toBe(20);

    $movements = StockMovement::where('product_id', $product->id)->get();
    expect($movements)->toHaveCount(2);

    $out = $movements->first(fn ($m) => $m->quantity < 0);
    expect($out->quantity)->toBe(-20)
        ->and($out->location_id)->toBe($warehouse->id);

    $in = $movements->first(fn ($m) => $m->quantity > 0);
    expect($in->quantity)->toBe(20)
        ->and($in->location_id)->toBe($store->id);
});

it('throws exception when source stock is insufficient', function () {
    $warehouse = Location::factory()->warehouse()->create();
    $store = Location::factory()->create();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $product = Product::factory()->create();

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $warehouse->id,
        'qty' => 5,
    ]);

    $transfer = StockTransfer::factory()->create([
        'from_location_id' => $warehouse->id,
        'to_location_id' => $store->id,
        'status' => 'pending',
        'created_by' => $admin->id,
    ]);

    StockTransferItem::create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'qty' => 20,
    ]);

    $action = app(CompleteStockTransfer::class);

    expect(fn () => $action->execute($transfer, $admin))
        ->toThrow(DomainException::class);
});

it('generates a transfer number automatically', function () {
    $transfer = StockTransfer::factory()->create();

    expect($transfer->transfer_number)->toStartWith('TRF-');
});
