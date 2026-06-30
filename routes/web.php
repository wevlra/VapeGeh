<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReceiptPrintDataController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'landing');

Route::middleware(['web', 'auth', 'throttle:120,1'])->group(function () {
    Route::get('/admin/history/{stockMovement}/receipt', ReceiptController::class)->name('admin.history.receipt');
    Route::get('/admin/history/{stockMovement}/invoice', InvoiceController::class)->name('admin.history.invoice');
    Route::get('/admin/sales/{sale}/receipt', [ReceiptController::class, 'sale'])->name('admin.sales.receipt');
    Route::get('/admin/history/{stockMovement}/receipt/print-data', ReceiptPrintDataController::class)
        ->name('admin.history.receipt.print-data');
});
