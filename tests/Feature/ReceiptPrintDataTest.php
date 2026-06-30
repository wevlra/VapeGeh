<?php

use App\Actions\CreateSale;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('blocks unauthenticated requests', function () {
    $movement = StockMovement::factory()->create();

    $this->getJson("/admin/history/{$movement->id}/receipt/print-data")
        ->assertUnauthorized();
});

it('blocks staff from another location', function () {
    $locationA = Location::factory()->create();
    $locationB = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $locationA->id,
    ]);
    $this->actingAs($staff);

    $movement = StockMovement::factory()->create([
        'location_id' => $locationB->id,
    ]);

    $this->getJson("/admin/history/{$movement->id}/receipt/print-data")
        ->assertForbidden();
});

it('allows admin to access any location', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $movement = StockMovement::factory()->create();

    $this->getJson("/admin/history/{$movement->id}/receipt/print-data")
        ->assertSuccessful();
});

it('returns receipt print data for a sale', function () {
    $location = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $location->id,
    ]);
    $this->actingAs($staff);

    $product = Product::factory()->create();
    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 100,
    ]);

    $action = app(CreateSale::class);
    $qty = 2;
    $unitPrice = (float) $product->default_price;
    $total = $unitPrice * $qty;
    $sale = $action->execute(
        user: $staff,
        locationId: $location->id,
        items: [
            ['product_id' => $product->id, 'qty' => $qty],
        ],
        paymentMethod: 'cash',
        paidAmount: $total,
    );

    $movement = $sale->stockMovements()->first();
    $response = $this->getJson("/admin/history/{$movement->id}/receipt/print-data");

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'reference', 'type', 'date',
        'store' => ['name', 'address', 'phone'],
        'logo_url', 'cashier', 'location',
        'items' => [['name', 'qty', 'price', 'subtotal']],
        'total', 'payment_method', 'notes',
    ]);

    expect($response['reference'])->toStartWith('INV-')
        ->and($response['type'])->toBe('STOK KELUAR')
        ->and($response['items'])->toHaveCount(1)
        ->and((float) $response['total'])->toBe($total)
        ->and($response['payment_method'])->toBe('Cash')
        ->and($response['logo_url'])->toContain('logo-thermal.png');
});
