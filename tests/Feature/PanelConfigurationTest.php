<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has admin panel accessible at /admin', function () {
    $response = $this->get('/admin');

    $response->assertRedirect();
});

it('has staff panel accessible at /staff', function () {
    $response = $this->get('/staff');

    $response->assertRedirect();
});

it('discovers admin panel resources from the correct namespace', function () {
    $providers = app()->getLoadedProviders();

    expect($providers)->toHaveKey('App\Providers\Filament\AdminPanelProvider');
});

it('discovers staff panel resources from the correct namespace', function () {
    $providers = app()->getLoadedProviders();

    expect($providers)->toHaveKey('App\Providers\Filament\StaffPanelProvider');
});
