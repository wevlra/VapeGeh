<?php

use App\Models\Location;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can print stock opname report as admin', function () {
    $admin = User::factory()->admin()->create();
    $location = Location::factory()->create();
    $product = Product::factory()->create();

    $opname = StockOpname::factory()->create([
        'location_id' => $location->id,
        'created_by' => $admin->id,
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    StockOpnameItem::factory()->create([
        'stock_opname_id' => $opname->id,
        'product_id' => $product->id,
        'system_qty' => 10,
        'actual_qty' => 15,
        'difference' => 5,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.stock-opnames.print', $opname))
        ->assertOk()
        ->assertSee('Laporan Penyesuaian Stok')
        ->assertSee($opname->opname_number)
        ->assertSee($product->name);
});

it('cannot print stock opname report as staff', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $location = Location::factory()->create();

    $opname = StockOpname::factory()->create([
        'location_id' => $location->id,
        'created_by' => $staff->id,
    ]);

    $this->actingAs($staff)
        ->get(route('admin.stock-opnames.print', $opname))
        ->assertForbidden();
});
