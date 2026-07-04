<?php

use App\Filament\Admin\Resources\StockOpnames\Pages\CreateStockOpname;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can create stock opname with populated items', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $location = Location::factory()->create();
    $product = Product::factory()->create();

    $component = Livewire::test(CreateStockOpname::class);

    // Fill location, it should trigger afterStateUpdated and populate items
    $component->set('data.location_id', $location->id);

    // Run the wizard save or create
    $component->call('create');

    $component->assertHasNoFormErrors();

    $this->assertDatabaseHas('stock_opnames', [
        'location_id' => $location->id,
    ]);

    $this->assertDatabaseHas('stock_opname_items', [
        'product_id' => $product->id,
        'system_qty' => 0,
        'actual_qty' => 0,
        'difference' => 0,
    ]);
});
