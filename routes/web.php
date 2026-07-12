<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SalesReportPrintController;
use App\Http\Controllers\StockOpnamePrintController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'landing');

Route::middleware(['web', 'auth', 'throttle:120,1'])->group(function () {
    Route::get('/admin/history/{stockMovement}/receipt', ReceiptController::class)->name('admin.history.receipt');
    Route::get('/admin/history/{stockMovement}/invoice', InvoiceController::class)->name('admin.history.invoice');
    Route::get('/admin/sales/{sale}/receipt', [ReceiptController::class, 'sale'])->name('admin.sales.receipt');
    Route::get('/admin/sales-report/print', SalesReportPrintController::class)->name('admin.sales-report.print');
    Route::get('/admin/stock-opnames/{stockOpname}/print', StockOpnamePrintController::class)->name('admin.stock-opnames.print');
});
