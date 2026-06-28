<?php

use App\Models\Location;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('has the location and user foundation columns', function () {
    expect(Schema::hasColumns('locations', [
        'name',
        'type',
        'address',
        'status',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('users', [
            'role',
            'location_id',
            'status',
        ]))->toBeTrue();
});

it('relates users to locations', function () {
    $location = Location::factory()->create();
    $user = User::factory()->create([
        'location_id' => $location->id,
    ]);

    expect($user->location->is($location))->toBeTrue()
        ->and($location->users()->whereKey($user)->exists())->toBeTrue();
});

it('gates filament panel access by role and active status', function () {
    $adminPanel = Panel::make()->id('admin');
    $staffPanel = Panel::make()->id('staff');

    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
    $staff = User::factory()->create([
        'role' => 'staff',
        'location_id' => Location::factory(),
        'status' => 'active',
    ]);
    $inactiveAdmin = User::factory()->create([
        'role' => 'admin',
        'status' => 'inactive',
    ]);

    expect($admin->canAccessPanel($adminPanel))->toBeTrue()
        ->and($admin->canAccessPanel($staffPanel))->toBeFalse()
        ->and($staff->canAccessPanel($adminPanel))->toBeFalse()
        ->and($staff->canAccessPanel($staffPanel))->toBeTrue()
        ->and($inactiveAdmin->canAccessPanel($adminPanel))->toBeFalse();
});
