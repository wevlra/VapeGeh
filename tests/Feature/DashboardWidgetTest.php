<?php

use App\Filament\Admin\Pages\Dashboard as AdminDashboard;
use App\Filament\Admin\Widgets\AdminExpenseCategoryChart;
use App\Filament\Admin\Widgets\AdminPaymentMethodChart;
use App\Filament\Admin\Widgets\AdminSalesChart;
use App\Filament\Admin\Widgets\AdminStatsOverview;
use App\Filament\Admin\Widgets\AdminStatsOverviewMobile;
use App\Filament\Staff\Pages\Dashboard as StaffDashboard;
use App\Filament\Staff\Widgets\StaffPaymentMethodChart;
use App\Filament\Staff\Widgets\StaffSalesChart;
use App\Filament\Staff\Widgets\StaffStatsOverview;
use App\Models\Expense;
use App\Models\Income;
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

    Livewire::test(StaffStatsOverview::class, ['pageFilters' => ['period' => 'today']])
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
        'total' => 250000,
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
        ->assertSee('Tunai')
        ->assertSee('Rp 250.000')
        ->assertSuccessful();
});

it('renders staff payment method chart widget with money amounts', function () {
    $location = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);

    Sale::factory()->create([
        'payment_method' => 'cash',
        'location_id' => $location->id,
        'user_id' => $staff->id,
        'total' => 350000,
    ]);

    $this->actingAs($staff);
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    Livewire::test(StaffPaymentMethodChart::class)
        ->assertSee('Penjualan per Metode Bayar')
        ->assertSee('Tunai')
        ->assertSee('Rp 350.000')
        ->assertSuccessful();
});

it('filters admin payment method chart by period', function () {
    $admin = User::factory()->admin()->create();

    Sale::factory()->create([
        'payment_method' => 'transfer',
        'user_id' => $admin->id,
        'total' => 75000,
        'created_at' => now()->subDays(10),
    ]);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(AdminPaymentMethodChart::class)
        ->assertSee('Penjualan per Metode Bayar')
        ->assertSee('Rp 75.000');

    Livewire::test(AdminPaymentMethodChart::class, ['pageFilters' => ['period' => 'today']])
        ->assertDontSee('Rp 75.000');
});

it('filters staff payment method chart by period', function () {
    $location = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);

    Sale::factory()->create([
        'payment_method' => 'cash',
        'location_id' => $location->id,
        'user_id' => $staff->id,
        'total' => 50000,
        'created_at' => now()->subDays(10),
    ]);

    $this->actingAs($staff);
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    Livewire::test(StaffPaymentMethodChart::class)
        ->assertSee('Penjualan per Metode Bayar')
        ->assertSee('Rp 50.000');

    Livewire::test(StaffPaymentMethodChart::class, ['pageFilters' => ['period' => 'today']])
        ->assertDontSee('Rp 50.000');
});

it('filters admin expense category chart by period', function () {
    $admin = User::factory()->admin()->create();

    Expense::factory()->create([
        'category' => 'utilities',
        'amount' => 120000,
        'date' => now()->subDays(10)->toDateString(),
    ]);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(AdminExpenseCategoryChart::class)
        ->assertSee('Pengeluaran per Kategori')
        ->assertSee('Utilitas');

    Livewire::test(AdminExpenseCategoryChart::class, ['pageFilters' => ['period' => 'today']])
        ->assertDontSee('Utilitas');

    Livewire::test(AdminExpenseCategoryChart::class, ['pageFilters' => ['period' => '7days']])
        ->assertDontSee('Utilitas');

    Livewire::test(AdminExpenseCategoryChart::class, ['pageFilters' => ['period' => '30days']])
        ->assertSee('Utilitas');
});

it('filters admin mobile stats overview by period', function () {
    $admin = User::factory()->admin()->create();

    Income::factory()->create([
        'amount' => 150000,
        'date' => now()->subDays(10)->toDateString(),
    ]);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(AdminStatsOverviewMobile::class)
        ->assertSee('Ringkasan Semua Waktu')
        ->assertSee('150.000');

    Livewire::test(AdminStatsOverviewMobile::class, ['pageFilters' => ['period' => 'today']])
        ->assertSee('Ringkasan Hari Ini')
        ->assertDontSee('150.000');

    Livewire::test(AdminStatsOverviewMobile::class, ['pageFilters' => ['period' => '7days']])
        ->assertSee('Ringkasan 7 Hari Terakhir')
        ->assertDontSee('150.000');

    Livewire::test(AdminStatsOverviewMobile::class, ['pageFilters' => ['period' => '30days']])
        ->assertSee('Ringkasan 30 Hari Terakhir')
        ->assertSee('150.000');
});

it('renders the shared period filter on the admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(AdminDashboard::class)
        ->assertSee('Periode')
        ->assertSee('Semua waktu')
        ->assertSee('7 hari terakhir')
        ->assertSee('30 hari terakhir')
        ->assertSet('filters.period', 'today')
        ->assertSuccessful();
});

it('renders the shared period filter on the staff dashboard', function () {
    $location = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);

    $this->actingAs($staff);
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    Livewire::test(StaffDashboard::class)
        ->assertSee('Periode')
        ->assertSee('Semua waktu')
        ->assertSee('7 hari terakhir')
        ->assertSee('30 hari terakhir')
        ->assertSet('filters.period', 'today')
        ->assertSuccessful();
});
