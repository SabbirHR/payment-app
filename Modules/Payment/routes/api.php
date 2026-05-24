<?php
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/payment')->group(function () {
    // GET test route - open in browser to test SSLCommerz instantly
    Route::get('test-pay', [\Modules\Payment\App\Http\Controllers\PaymentController::class, 'testPay']);

    Route::post('pay', [\Modules\Payment\App\Http\Controllers\PaymentController::class, 'pay']);
    Route::post('validate', [\Modules\Payment\App\Http\Controllers\PaymentController::class, 'validateT']);
    Route::get('validate', [\Modules\Payment\App\Http\Controllers\PaymentController::class, 'validateT']); // SSLCommerz can redirect via GET
});
