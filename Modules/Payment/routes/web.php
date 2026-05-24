<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/checkout', function () {
        return view('payment::checkout');
    });

    Route::post('/checkout', [\Modules\Payment\App\Http\Controllers\PaymentController::class, 'processCheckout'])->name('payment.checkout.process');

    // Web Validation Routes
    Route::get('/payment/validate', [\Modules\Payment\App\Http\Controllers\PaymentController::class, 'validateT'])->name('payment.validate.web');
    Route::post('/payment/validate', [\Modules\Payment\App\Http\Controllers\PaymentController::class, 'validateT']);
});
