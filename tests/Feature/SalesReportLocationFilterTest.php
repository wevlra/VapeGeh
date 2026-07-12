<?php

use App\Filament\Admin\Pages\SalesReport;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can filter sales report by location', function () {
    $admin = User::factory()->admin()->create();

    $locationA = Location::factory()->create(['name' => 'Toko A']);
    $locationB = Location::factory()->create(['name' => 'Toko B']);

    $product = Product::factory()->create();

    $saleA = Sale::factory()->create([
        'location_id' => $locationA->id,
        'user_id' => $admin->id,
    ]);

    $saleB = Sale::factory()->create([
        'location_id' => $locationB->id,
        'user_id' => $admin->id,
    ]);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(SalesReport::class)
        ->assertCanSeeTableRecords([$saleA, $saleB])
        ->filterTable('location_name', $locationA->name)
        ->assertCanSeeTableRecords([$saleA])
        ->assertCanNotSeeTableRecords([$saleB]);
});
