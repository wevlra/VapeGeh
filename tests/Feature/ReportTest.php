<?php

use App\Models\Location;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    $this->actingAs($staff);
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    $response = $this->get('/staff/sales-report');

    $response->assertSuccessful();
});
