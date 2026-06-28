<?php

use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'landing');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/stock-movements/{stockMovement}/receipt', function (StockMovement $stockMovement) {
        $stockMovement->load(['product', 'location', 'creator', 'buyer', 'related']);

        return view('receipts.receipt', ['movement' => $stockMovement]);
    })->name('admin.stock-movements.receipt');

    Route::get('/admin/stock-movements/{stockMovement}/invoice', function (StockMovement $stockMovement) {
        if ($stockMovement->type !== 'out') {
            abort(404);
        }
        $stockMovement->load(['product', 'location', 'creator', 'buyer', 'related']);

        return \Spatie\LaravelPdf\Facades\Pdf::view('invoices.invoice', ['movement' => $stockMovement])
            ->name('invoice-'.($stockMovement->related?->invoice_number ?? 'SM-'.$stockMovement->id).'.pdf')
            ->download();
    })->name('admin.stock-movements.invoice');

    Route::get('/admin/sales/{sale}/receipt', function (Sale $sale) {
        $movement = $sale->stockMovements()->first();
        if (! $movement) {
            abort(404);
        }
        $movement->load(['product', 'location', 'creator', 'buyer', 'related']);

        return view('receipts.receipt', ['movement' => $movement]);
    })->name('admin.sales.receipt');
});
