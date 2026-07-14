<?php

use App\Filament\Admin\Widgets\AdminStockAssetMobile;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders admin stock asset mobile widget with correct data', function () {
    $admin = User::factory()->admin()->create();
    $location = Location::factory()->create();
    $product = Product::factory()->create();

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 50,
    ]);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(AdminStockAssetMobile::class)
        ->assertSee('Total Stok')
        ->assertSee('Total Aset')
        ->assertSuccessful();
});
