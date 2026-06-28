<?php

use App\Actions\CompleteStockTransfer;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('actions');

it('transfers stock between locations and creates movements', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $warehouse = Location::factory()->warehouse()->create();
    $store = Location::factory()->create();
    $product = Product::factory()->create();

    $sourceStock = Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $warehouse->id,
        'qty' => 20,
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
        'qty' => 5,
    ]);

    app(CompleteStockTransfer::class)->execute($transfer, $admin);

    $sourceStock->refresh();
    expect($sourceStock->qty)->toBe(15);

    $destStock = Stock::where('product_id', $product->id)
        ->where('location_id', $store->id)
        ->first();
    expect($destStock->qty)->toBe(5);

    $transfer->refresh();
    expect($transfer->status)->toBe('completed');
});

it('throws DomainException when transfer is not pending', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $transfer = StockTransfer::factory()->completed()->create([
        'created_by' => $admin->id,
    ]);

    app(CompleteStockTransfer::class)->execute($transfer, $admin);
})->throws(DomainException::class, 'Only pending transfers');

it('throws DomainException when source and destination are the same', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $location = Location::factory()->create();

    $transfer = StockTransfer::factory()->create([
        'from_location_id' => $location->id,
        'to_location_id' => $location->id,
        'status' => 'pending',
        'created_by' => $admin->id,
    ]);

    app(CompleteStockTransfer::class)->execute($transfer, $admin);
})->throws(DomainException::class, 'same location');
