<?php

use App\Models\Location;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to access sales report print page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $response = $this->get(route('admin.sales-report.print'));

    $response->assertSuccessful();
    $response->assertSee('Laporan Penjualan');
});

it('filters print results by location name', function () {
    $admin = User::factory()->admin()->create();

    $locationA = Location::factory()->create(['name' => 'Toko A']);
    $locationB = Location::factory()->create(['name' => 'Toko B']);

    Sale::factory()->create([
        'location_id' => $locationA->id,
        'user_id' => $admin->id,
    ]);

    Sale::factory()->create([
        'location_id' => $locationB->id,
        'user_id' => $admin->id,
    ]);

    $this->actingAs($admin);

    $response = $this->get(route('admin.sales-report.print', ['location_name' => 'Toko A']));

    $response->assertSuccessful();
    $response->assertSee('Toko A');
    $response->assertDontSee('Toko B');
});

it('filters print results by payment method', function () {
    $admin = User::factory()->admin()->create();
    $location = Location::factory()->create();

    Sale::factory()->create([
        'location_id' => $location->id,
        'user_id' => $admin->id,
        'payment_method' => 'cash',
    ]);

    Sale::factory()->create([
        'location_id' => $location->id,
        'user_id' => $admin->id,
        'payment_method' => 'qris',
    ]);

    $this->actingAs($admin);

    $response = $this->get(route('admin.sales-report.print', ['payment_method' => 'cash']));

    $response->assertSuccessful();
    $response->assertSee('Tunai');
});

it('denies non-admin access to print page', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff);

    $response = $this->get(route('admin.sales-report.print'));

    $response->assertForbidden();
});
