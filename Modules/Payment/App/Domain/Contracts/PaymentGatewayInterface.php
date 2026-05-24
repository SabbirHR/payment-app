<?php
namespace Modules\Payment\App\Domain\Contracts;

use Modules\Payment\App\Domain\DTO\PaymentRequestDto;

interface PaymentGatewayInterface
{
    /**
     * Process a payment request.
     */
    public function pay(PaymentRequestDto $request): array;

    /**
     * Validate a transaction after customer returns from gateway.
     */
    public function validateTransaction(string $validationId): array;

    /**
     * Refund a completed transaction.
     */
    public function refund(string $transactionId, float $amount): array;
}
