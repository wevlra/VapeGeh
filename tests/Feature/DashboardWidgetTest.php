<?php

use App\Filament\Admin\Widgets\AdminPaymentMethodChart;
use App\Filament\Admin\Widgets\AdminSalesChart;
use App\Filament\Admin\Widgets\AdminStatsOverview;
use App\Filament\Staff\Widgets\StaffSalesChart;
use App\Filament\Staff\Widgets\StaffStatsOverview;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockEntry;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders admin stats overview widget with global data', function () {
    $admin = User::factory()->admin()->create();
    Product::factory()->count(3)->create();
    Location::factory()->count(2)->create();

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(AdminStatsOverview::class)
        ->assertSee('Total Aset')
        ->assertSee('Stok Masuk')
        ->assertSee('Stok Keluar')
        ->assertSee('Total Stok')
        ->assertSee('Pendapatan')
        ->assertSee('Untung')
        ->assertSuccessful();
});

it('renders staff stats overview widget with scoped data', function () {
    $location = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);
    $product = Product::factory()->create();

    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 25,
    ]);

    $this->actingAs($staff);
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    Livewire::test(StaffStatsOverview::class)
        ->assertSee('Total Penjualan')
        ->assertSee('Unit Stok')
        ->assertSuccessful();
});

it('filters staff stats overview by period', function () {
    $location = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);
    $product = Product::factory()->create();

    $this->actingAs($staff);
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    Livewire::test(StaffStatsOverview::class)
        ->set('period', 'today')
        ->assertSet('period', 'today')
        ->assertSee('Hari ini')
        ->assertSuccessful();
});

it('renders admin sales chart widget', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(AdminSalesChart::class)
        ->assertSee('Tren Penjualan')
        ->assertSuccessful();
});

it('renders staff sales chart widget with scoped data', function () {
    $location = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);

    $this->actingAs($staff);
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    Livewire::test(StaffSalesChart::class)
        ->assertSee('Tren Penjualan')
        ->assertSuccessful();
});

it('renders admin payment method chart widget with sales and stock out data', function () {
    $admin = User::factory()->admin()->create();
    $location = Location::factory()->create();

    Sale::factory()->create([
        'payment_method' => 'cash',
        'location_id' => $location->id,
        'user_id' => $admin->id,
    ]);

    StockEntry::create([
        'type' => 'out',
        'payment_method' => 'transfer',
        'location_id' => $location->id,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(AdminPaymentMethodChart::class)
        ->assertSee('Penjualan per Metode Bayar')
        ->assertSuccessful();
});
