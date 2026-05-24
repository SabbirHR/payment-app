<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/checkout', function () {
        return view('payment::checkout');
    });

    Route::post('/checkout', [\Modules\Payment\App\Http\Controllers\PaymentController::class, 'processCheckout'])->name('payment.checkout.process');
});
