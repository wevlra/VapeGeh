<?php

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->location = Location::factory()->create(['type' => 'warehouse', 'status' => 'active']);
    $this->product = Product::factory()->create(['purchase_price' => 0]);
});

it('sets stocks price to unit price on first stock entry', function () {
    Stock::create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'qty' => 10,
        'purchase_price' => 100000.0,
    ]);

    expect($this->location->total_asset)->toBe(1000000.0);
});

it('computes weighted average cost on second stock entry', function () {
    // Entry 1: 10 units @ 100,000
    $stock = Stock::create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'qty' => 10,
        'purchase_price' => 100000.0,
    ]);

    // Entry 2: 10 units @ 120,000
    // WAC = (10 × 100,000 + 10 × 120,000) / 20 = 110,000
    $newPrice = 120000.0;
    $newQty = 10;
    $oldQty = (int) $stock->qty;
    $oldPrice = (float) $stock->purchase_price;
    $newTotalQty = $oldQty + $newQty;
    $wac = (($oldQty * $oldPrice) + ($newQty * $newPrice)) / $newTotalQty;

    $stock->qty = $newTotalQty;
    $stock->purchase_price = round($wac, 2);
    $stock->save();

    expect($stock->purchase_price)->toBe(110000.0);
    // Total aset = 20 × 110,000 = 2,200,000 = total stok masuk (10×100k + 10×120k)
    expect($this->location->total_asset)->toBe(2200000.0);
});

it('total asset equals total stock in value when using wac', function () {
    // Entry 1: 5 units @ 80,000 = 400,000
    $stock = Stock::create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'qty' => 5,
        'purchase_price' => 80000.0,
    ]);

    $entries = [
        ['qty' => 5, 'purchase_price' => 100000.0],
        ['qty' => 10, 'purchase_price' => 110000.0],
    ];

    $totalStockIn = 5 * 80000.0;

    foreach ($entries as $entry) {
        $totalStockIn += $entry['qty'] * $entry['purchase_price'];
        $oldQty = (int) $stock->qty;
        $oldPrice = (float) $stock->purchase_price;
        $newQty = $entry['qty'];
        $newPrice = $entry['purchase_price'];
        $newTotalQty = $oldQty + $newQty;
        $wac = (($oldQty * $oldPrice) + ($newQty * $newPrice)) / $newTotalQty;

        $stock->qty = $newTotalQty;
        $stock->purchase_price = round($wac, 2);
        $stock->save();
    }

    // Total stok masuk = 400,000 + 500,000 + 1,100,000 = 2,000,000
    expect($totalStockIn)->toBe(2000000.0);
    // Allow max 1 Rp rounding error
    expect(abs($this->location->total_asset - $totalStockIn))->toBeLessThanOrEqual(1.0);
});

it('total asset uses stocks price not products purchase price', function () {
    $this->product->update(['purchase_price' => 99999]);

    Stock::create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'qty' => 10,
        'purchase_price' => 100000.0,
    ]);

    // Should use stocks.price (100,000 × 10 = 1,000,000), not products.purchase_price (99,999 × 10 = 999,990)
    expect($this->location->total_asset)->toBe(1000000.0);
});
