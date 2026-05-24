<?php
namespace Modules\Payment\App\Traits;

use Illuminate\Support\Facades\Log;
use Modules\Payment\App\Events\PaymentSuccessful;

trait HandlesPaymentProcess
{
    /**
     * Executes business operations after a successful payment.
     * Fires an event so host modules (Flight, Hotel, LMS) can listen and handle their own logic.
     */
    protected function handlePaymentProcess($invoiceable): void
    {
        try {
            Log::info('Payment processed successfully for invoiceable ID: ' . ($invoiceable->id ?? 'unknown'));
            
            // Fire an event that ANY other module can listen to!
            event(new PaymentSuccessful($this));
            
        } catch (\Throwable $e) {
            Log::error('Payment processing failed: ' . $e->getMessage());
        }
    }
}
