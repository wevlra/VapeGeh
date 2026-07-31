<?php

use App\Filament\Admin\Widgets\AdminOtherInformationsMobile;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders admin other informations mobile widget with correct data', function () {
    $location = Location::factory()->create();
    $admin = User::factory()->admin()->create(['location_id' => $location->id]);
    $product = Product::factory()->create();

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 50,
    ]);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(AdminOtherInformationsMobile::class)
        ->assertSee('Total Stok')
        ->assertSee('Total Aset')
        ->assertSee('Total Produk')
        ->assertSee('Total Staff')
        ->assertSuccessful();
});
