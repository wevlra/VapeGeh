<?php

use App\Filament\Admin\Resources\StockOpnames\Pages\StockOpnameCreate;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can create stock opname with manually added items', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $location = Location::factory()->create();
    $product = Product::factory()->create();

    $component = Livewire::test(StockOpnameCreate::class);

    $component->set('data.location_id', $location->id);

    // Call the table action to add an item
    $component->callTableAction('addItem', $product, ['actual_qty' => 5, 'notes' => 'Test notes']);

    // Call save
    $component->call('save');

    $this->assertDatabaseHas('stock_opnames', [
        'location_id' => $location->id,
        'status' => 'draft',
    ]);

    $this->assertDatabaseHas('stock_opname_items', [
        'product_id' => $product->id,
        'system_qty' => 0,
        'actual_qty' => 5,
        'difference' => 5,
        'notes' => 'Test notes',
    ]);
});
