<?php

use App\Http\Controllers\ReceiptApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/receipt/{stockMovement}', [ReceiptApiController::class, 'show'])
        ->name('api.receipt.show');

    Route::get('/receipt/sale/{sale}', [ReceiptApiController::class, 'sale'])
        ->name('api.receipt.sale');
});
