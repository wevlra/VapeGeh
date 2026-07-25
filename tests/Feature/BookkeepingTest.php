<?php

use App\Filament\Admin\Resources\Expenses\ExpenseResource;
use App\Filament\Admin\Resources\Incomes\IncomeResource;
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

it('correctly identifies manual vs system income and expense records', function () {
    $manualIncome = Income::factory()->create(['description' => 'Pendapatan sewa booth']);
    $systemIncome = Income::factory()->create(['description' => 'Penjualan POS #INV-260725-0001']);
    $stockOutIncome = Income::factory()->create(['description' => 'Stok Keluar ke Customer (Entry #123)']);

    expect($manualIncome->isManual())->toBeTrue()
        ->and($systemIncome->isManual())->toBeFalse()
        ->and($stockOutIncome->isManual())->toBeFalse();

    $manualExpense = Expense::factory()->create(['description' => 'Bayar sewa toko']);
    $systemExpense = Expense::factory()->create(['description' => 'Stok Masuk dari Vendor (Entry #456)']);

    expect($manualExpense->isManual())->toBeTrue()
        ->and($systemExpense->isManual())->toBeFalse();

    expect(IncomeResource::canEdit($manualIncome))->toBeTrue()
        ->and(IncomeResource::canEdit($systemIncome))->toBeFalse()
        ->and(ExpenseResource::canEdit($manualExpense))->toBeTrue()
        ->and(ExpenseResource::canEdit($systemExpense))->toBeFalse();
});
