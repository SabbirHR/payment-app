<?php
namespace Modules\Payment\App\Traits;

use Modules\Payment\App\Domain\Exceptions\PaymentFailedException;
use Illuminate\Support\Facades\Log;

trait HandlesGatewayExceptions
{
    /**
     * Wrap gateway calls in a centralized try-catch block.
     */
    protected function withExceptionHandling(callable $callback)
    {
        try {
            return $callback();
        } catch (PaymentFailedException $e) {
            Log::error('Gateway communication error: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected payment error: ' . $e->getMessage());
            throw new PaymentFailedException('An unexpected error occurred during payment processing.', 500, $e);
        }
    }
}
