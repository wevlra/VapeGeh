<?php

use App\Filament\Admin\Pages\SalesReportStats;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockEntry;
use App\Models\StockEntryItem;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('allows admin to access stock report', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $response = $this->get('/admin/stock-report');

    $response->assertSuccessful();
});

it('allows admin to access sales report', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $response = $this->get('/admin/sales-report');

    $response->assertSuccessful();
});

it('allows admin to access bookkeeping report', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $response = $this->get('/admin/bookkeeping-report');

    $response->assertSuccessful();
});

it('allows staff to access sales report scoped to location', function () {
    $location = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);

    $this->actingAs($staff, 'staff');
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    $response = $this->get('/staff/sales-report');

    $response->assertSuccessful();
});

it('includes additional costs in sales report total revenue', function () {
    $location = Location::factory()->create();
    $admin = User::factory()->admin()->create();

    $product = Product::factory()->create(['purchase_price' => 10000, 'price_1' => 20000]);

    $stockEntry = StockEntry::create([
        'type' => 'out',
        'location_id' => $location->id,
        'created_by' => $admin->id,
        'additional_costs' => [
            ['name' => 'Ongkir', 'amount' => 15000],
            ['name' => 'Packing', 'amount' => 5000],
        ],
    ]);

    StockEntryItem::create([
        'stock_entry_id' => $stockEntry->id,
        'product_id' => $product->id,
        'qty' => 2,
        'unit_price' => 20000,
    ]);

    expect(StockEntry::find($stockEntry->id)->total_amount)->toBe(60000.0);

    Livewire::test(SalesReportStats::class)
        ->assertSee('60.000');
});
