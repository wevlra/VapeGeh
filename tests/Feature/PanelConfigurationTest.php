<?php

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has admin panel accessible at /admin', function () {
    $response = $this->get('/admin');

    $response->assertRedirect();
});

it('has admin login page accessible at /admin/login', function () {
    $response = $this->get('/admin/login');

    $response->assertSuccessful()
        ->assertSee('VAPEGEH ADMIN');
});

it('has staff panel accessible at /staff', function () {
    $response = $this->get('/staff');

    $response->assertRedirect();
});

it('has staff history page accessible for logged in staff user', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => Location::factory(),
        'status' => 'active',
    ]);
    $this->actingAs($staff);

    $response = $this->get('/staff/history');

    $response->assertSuccessful();
});

it('has staff login page accessible at /staff/login', function () {
    $response = $this->get('/staff/login');

    $response->assertSuccessful()
        ->assertSee('VAPEGEH STAFF');
});

it('discovers admin panel resources from the correct namespace', function () {
    $providers = app()->getLoadedProviders();

    expect($providers)->toHaveKey('App\Providers\Filament\AdminPanelProvider');
});

it('discovers staff panel resources from the correct namespace', function () {
    $providers = app()->getLoadedProviders();

    expect($providers)->toHaveKey('App\Providers\Filament\StaffPanelProvider');
});
