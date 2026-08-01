<?php

use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('tests the stock opname edit route', function () {
    $this->withoutExceptionHandling();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $opname = StockOpname::factory()->create(['status' => 'draft']);

    $response = $this->get('/admin/stock-opnames/'.$opname->id.'/edit');
    $response->assertStatus(200);
});
