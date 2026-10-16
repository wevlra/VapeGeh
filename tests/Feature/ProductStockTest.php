<?php

use App\Filament\Admin\Resources\Locations\Pages\ViewLocation;
use App\Filament\Admin\Resources\Locations\RelationManagers\StocksRelationManager;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('has the correct product columns', function () {
    expect(Schema::hasColumns('products', [
        'name', 'purchase_price',
    ]))->toBeTrue();
});

it('has the correct stock columns and unique constraint', function () {
    expect(Schema::hasColumns('stocks', [
        'product_id', 'location_id', 'qty',
    ]))->toBeTrue();

    $indexes = Schema::getIndexes('stocks');
    $uniqueProductLocation = collect($indexes)->contains(function ($index) {
        return $index['unique']
            && is_array($index['columns'])
            && in_array('product_id', $index['columns'])
            && in_array('location_id', $index['columns']);
    });
    expect($uniqueProductLocation)->toBeTrue();
});

it('has the correct stock movement columns', function () {
    expect(Schema::hasColumns('stock_movements', [
        'product_id', 'location_id', 'type', 'quantity',
        'related_type', 'related_id', 'notes', 'created_by',
    ]))->toBeTrue();
});

it('creates a product with factory', function () {
    $product = Product::factory()->create();

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->name)->not->toBeEmpty();
});

it('creates stock for a product at a location', function () {
    $stock = Stock::factory()->create([
        'qty' => 50,
    ]);

    expect($stock->product)->toBeInstanceOf(Product::class)
        ->and($stock->location)->toBeInstanceOf(Location::class)
        ->and($stock->qty)->toBe(50);
});

it('relates stock to product and location', function () {
    $product = Product::factory()->create();
    $location = Location::factory()->create();

    $stock = Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
    ]);

    expect($stock->product->is($product))->toBeTrue()
        ->and($stock->location->is($location))->toBeTrue();
});

it('computes total asset per location and globally', function () {
    $warehouse = Location::factory()->warehouse()->create();
    $store = Location::factory()->create();
    $product1 = Product::factory()->create(['price_1' => 100000]);
    $product2 = Product::factory()->create(['price_1' => 80000]);

    Stock::create(['product_id' => $product1->id, 'location_id' => $warehouse->id, 'qty' => 5, 'purchase_price' => 100000]);
    Stock::create(['product_id' => $product2->id, 'location_id' => $store->id, 'qty' => 3, 'purchase_price' => 80000]);

    expect((float) $warehouse->total_asset)->toBe(500000.0)
        ->and((float) $store->total_asset)->toBe(240000.0)
        ->and(Location::getTotalAssetOfAll())->toBe(740000.0);
});

it('renders product category in location stocks relation manager', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $category = Category::create(['name' => 'Liquid Freebase']);
    $product = Product::factory()->create([
        'name' => 'Oat Drips V1',
        'category_id' => $category->id,
    ]);
    $location = Location::factory()->create();
    $stock = Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 15,
    ]);

    Livewire::test(StocksRelationManager::class, [
        'ownerRecord' => $location,
        'pageClass' => ViewLocation::class,
    ])
        ->assertCanSeeTableRecords([$stock])
        ->assertTableColumnExists('product.category.name')
        ->assertSee('Liquid Freebase');
});

it('orders location stock export query by category name alphabetically', function () {
    $location = Location::factory()->create();

    $catZ = Category::create(['name' => 'Z Coil']);
    $catA = Category::create(['name' => 'A Liquid']);
    $catB = Category::create(['name' => 'B Device']);

    $prodZ = Product::factory()->create(['name' => 'Z Product', 'category_id' => $catZ->id]);
    $prodA = Product::factory()->create(['name' => 'A Product', 'category_id' => $catA->id]);
    $prodB = Product::factory()->create(['name' => 'B Product', 'category_id' => $catB->id]);

    $stockZ = Stock::factory()->create(['location_id' => $location->id, 'product_id' => $prodZ->id, 'qty' => 10]);
    $stockA = Stock::factory()->create(['location_id' => $location->id, 'product_id' => $prodA->id, 'qty' => 20]);
    $stockB = Stock::factory()->create(['location_id' => $location->id, 'product_id' => $prodB->id, 'qty' => 30]);

    $viewLocation = new ViewLocation;
    $viewLocation->record = $location;

    $results = $viewLocation->buildExportQuery()->get();

    expect($results->pluck('id')->all())->toBe([
        $stockA->id,
        $stockB->id,
        $stockZ->id,
    ]);
});
