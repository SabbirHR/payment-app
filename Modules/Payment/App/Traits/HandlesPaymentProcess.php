<?php
namespace Modules\Payment\App\Traits;

use Illuminate\Support\Facades\Log;

trait HandlesPaymentProcess
{
    /**
     * Executes business operations after a successful payment.
     */
    protected function handlePaymentProcess($invoiceable): void
    {
        if (method_exists($this, 'processFlightPayment')) {
            $this->processFlightPayment($invoiceable);
        }
    }

    protected function processFlightPayment($invoiceable): void
    {
        try {
            // Placeholder for booking update logic (as per architecture guide)
            Log::info('Payment processed successfully for invoiceable ID: ' . $invoiceable->id ?? 'unknown');
            
            // e.g. Update Admin Booking Status
            // Update Flight sequences with PNR
            // Update Passenger Ticket Numbers (Only valid 13-digit numbers)
            
        } catch (\Throwable $e) {
            Log::error('Payment processing failed: ' . $e->getMessage());
        }
    }
}
