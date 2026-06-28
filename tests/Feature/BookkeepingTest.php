<?php

use App\Models\Expense;
use App\Models\Income;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('has the correct incomes columns', function () {
    expect(Schema::hasColumns('incomes', [
        'location_id', 'category', 'description', 'amount', 'date', 'created_by',
    ]))->toBeTrue();
});

it('has the correct expenses columns', function () {
    expect(Schema::hasColumns('expenses', [
        'location_id', 'category', 'description', 'amount', 'date', 'created_by',
    ]))->toBeTrue();
});

it('creates an income with valid category', function () {
    $income = Income::factory()->create(['category' => 'sale']);

    expect($income->category)->toBe('sale')
        ->and($income->location)->toBeInstanceOf(Location::class)
        ->and($income->creator)->toBeInstanceOf(User::class);
});

it('creates an expense with valid category', function () {
    $expense = Expense::factory()->create(['category' => 'purchase']);

    expect($expense->category)->toBe('purchase')
        ->and($expense->location)->toBeInstanceOf(Location::class)
        ->and($expense->creator)->toBeInstanceOf(User::class);
});

it('relates income to location and creator', function () {
    $location = Location::factory()->create();
    $user = User::factory()->admin()->create();

    $income = Income::factory()->create([
        'location_id' => $location->id,
        'created_by' => $user->id,
    ]);

    expect($income->location->is($location))->toBeTrue()
        ->and($income->creator->is($user))->toBeTrue();
});

it('relates expense to location and creator', function () {
    $location = Location::factory()->create();
    $user = User::factory()->admin()->create();

    $expense = Expense::factory()->create([
        'location_id' => $location->id,
        'created_by' => $user->id,
    ]);

    expect($expense->location->is($location))->toBeTrue()
        ->and($expense->creator->is($user))->toBeTrue();
});
