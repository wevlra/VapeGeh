<?php

use App\Filament\Admin\Pages\Pos as AdminPos;
use App\Models\Income;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('allows admin pos checkout and creates an income record', function () {
    $location = Location::factory()->create();
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
    $this->actingAs($admin, 'web');

    $product = Product::factory()->create();
    Stock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'qty' => 10,
    ]);

    Livewire::test(AdminPos::class)
        ->set('items', [
            [
                'product_id' => $product->id,
                'location_id' => $location->id,
                'qty' => 2,
                'price' => (float) $product->default_price,
            ],
        ])
        ->mountAction('checkout')
        ->callMountedAction([
            'payment_method' => 'cash',
            'paid_amount' => (float) $product->default_price * 2,
        ])
        ->assertHasNoActionErrors();

    expect(Income::query()->count())->toBe(1);
});
