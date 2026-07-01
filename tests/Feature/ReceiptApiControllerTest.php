<?php

use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('returns receipt JSON for a sale movement', function () {
    $user = User::factory()->admin()->create();
    $sale = Sale::factory()
        ->hasItems(2)
        ->create();
    $movement = StockMovement::factory()
        ->for($sale, 'related')
        ->create(['type' => 'out']);

    actingAs($user)
        ->getJson("/api/receipt/{$movement->id}")
        ->assertOk()
        ->assertJsonStructure([
            'ref_number',
            'type_label',
            'date',
            'staff',
            'location',
            'items' => [
                '*' => ['product', 'qty', 'price', 'subtotal'],
            ],
            'total',
            'paid_amount',
            'change',
            'payment_method',
        ]);
});

it('returns 403 for staff at wrong location', function () {
    $location = Location::factory()->create();
    $otherLocation = Location::factory()->create();
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => $otherLocation->id,
    ]);
    $movement = StockMovement::factory()->create([
        'location_id' => $location->id,
    ]);

    actingAs($staff)
        ->getJson("/api/receipt/{$movement->id}")
        ->assertForbidden();
});

it('returns receipt from a sale route', function () {
    $user = User::factory()->admin()->create();
    $sale = Sale::factory()
        ->hasItems(1)
        ->create();
    StockMovement::factory()
        ->for($sale, 'related')
        ->create(['type' => 'out']);

    actingAs($user)
        ->getJson("/api/receipt/sale/{$sale->id}")
        ->assertOk();
});

it('returns 403 for non-admin non-staff role', function () {
    $user = User::factory()->admin()->create();
    DB::unprepared('PRAGMA ignore_check_constraints = ON');
    DB::table('users')->where('id', $user->id)->update(['role' => 'cashier']);
    DB::unprepared('PRAGMA ignore_check_constraints = OFF');
    $user->refresh();
    $movement = StockMovement::factory()->create();

    actingAs($user)
        ->getJson("/api/receipt/{$movement->id}")
        ->assertForbidden();
});

it('returns receipt JSON for a stock transfer movement', function () {
    $user = User::factory()->admin()->create();
    $fromLocation = Location::factory()->warehouse()->create();
    $toLocation = Location::factory()->create();
    $transfer = StockTransfer::factory()
        ->completed()
        ->create([
            'from_location_id' => $fromLocation->id,
            'to_location_id' => $toLocation->id,
        ]);
    $transfer->items()->createMany([
        ['product_id' => Product::factory()->create()->id, 'qty' => 5],
        ['product_id' => Product::factory()->create()->id, 'qty' => 3],
    ]);
    $movement = StockMovement::factory()
        ->for($transfer, 'related')
        ->create([
            'type' => 'transfer_in',
            'location_id' => $toLocation->id,
        ]);

    actingAs($user)
        ->getJson("/api/receipt/{$movement->id}")
        ->assertOk()
        ->assertJsonStructure([
            'ref_number',
            'type_label',
            'date',
            'staff',
            'location',
            'items',
            'total',
            'paid_amount',
            'change',
            'payment_method',
        ])
        ->assertJson([
            'total' => null,
            'paid_amount' => null,
            'change' => null,
            'payment_method' => null,
        ]);
});
