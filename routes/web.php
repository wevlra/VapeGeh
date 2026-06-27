<?php

use App\Models\StockMovement;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'landing');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/stock-movements/{stockMovement}/receipt', function (StockMovement $stockMovement) {
        $stockMovement->load(['product', 'location', 'creator', 'buyer', 'related']);

        return view('receipts.receipt', ['movement' => $stockMovement]);
    })->name('admin.stock-movements.receipt');
});
