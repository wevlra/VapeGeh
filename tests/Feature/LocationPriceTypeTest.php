<?php

use App\Actions\CompleteStockTransfer;
use App\Filament\Admin\Resources\Locations\Pages\CreateLocation;
use App\Filament\Staff\Pages\Pos as StaffPos;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('allows selecting reseller as the price type when creating a location', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(CreateLocation::class)
        ->fillForm([
            'name' => 'Toko Reseller',
            'type' => 'store',
            'price_type' => 'reseller',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $location = Location::where('name', 'Toko Reseller')->first();

    expect($location)->not->toBeNull()
        ->and($location->price_type)->toBe('reseller');
});

it('supports reseller price type on a location', function () {
    $location = Location::factory()->create(['price_type' => 'reseller']);
    $product = Product::factory()->create([
        'price_1' => 10000,
        'price_2' => 20000,
        'reseller_price' => 15000,
    ]);

    expect($location->fresh()->price_type)->toBe('reseller')
        ->and($location->priceField())->toBe('reseller_price')
        ->and($location->priceFor($product))->toBe(15000.0);
});

it('falls back to harga 1 when the selected price tier is not set', function () {
    $resellerLocation = Location::factory()->create(['price_type' => 'reseller']);
    $harga2Location = Location::factory()->create(['price_type' => '2']);
    $product = Product::factory()->create([
        'price_1' => 10000,
        'price_2' => 0,
        'reseller_price' => 0,
    ]);

    expect($resellerLocation->priceFieldFor($product))->toBe('price_1')
        ->and($resellerLocation->priceFor($product))->toBe(10000.0)
        ->and($harga2Location->priceFor($product))->toBe(10000.0);
});

it('uses reseller price when completing a transfer to a reseller price type location', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $warehouse = Location::factory()->warehouse()->create();
    $store = Location::factory()->create(['price_type' => 'reseller']);
    $product = Product::factory()->create([
        'price_1' => 10000,
        'price_2' => 20000,
        'reseller_price' => 15000,
    ]);

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $warehouse->id,
        'qty' => 10,
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
        'qty' => 2,
    ]);

    app(CompleteStockTransfer::class)->execute($transfer, $admin);

    $destinationStock = Stock::where('product_id', $product->id)
        ->where('location_id', $store->id)
        ->first();

    expect($destinationStock)->not->toBeNull()
        ->and((float) $destinationStock->purchase_price)->toBe(15000.0);
});

it('charges the reseller price on the staff pos for a reseller price type location', function () {
    $location = Location::factory()->create(['price_type' => 'reseller']);
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);
    $product = Product::factory()->create([
        'price_1' => 10000,
        'price_2' => 20000,
        'reseller_price' => 15000,
    ]);

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 10,
    ]);

    $fallbackProduct = Product::factory()->create([
        'price_1' => 12000,
        'price_2' => 0,
        'reseller_price' => 0,
    ]);

    Stock::factory()->create([
        'product_id' => $fallbackProduct->id,
        'location_id' => $location->id,
        'qty' => 10,
    ]);

    $this->actingAs($staff);
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    $component = Livewire::test(StaffPos::class)
        ->set('items', [
            ['product_id' => $product->id, 'qty' => 2],
            ['product_id' => $fallbackProduct->id, 'qty' => 1],
        ]);

    $cartItems = $component->instance()->getCartItems();

    expect($cartItems)->toHaveCount(2)
        ->and((float) $cartItems[0]['price'])->toBe(15000.0)
        ->and((float) $cartItems[0]['subtotal'])->toBe(30000.0)
        ->and((float) $cartItems[1]['price'])->toBe(12000.0)
        ->and((float) $cartItems[1]['subtotal'])->toBe(12000.0)
        ->and($component->instance()->getCartTotal())->toBe(42000.0);
});
