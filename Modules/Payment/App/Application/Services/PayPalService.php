<?php
namespace Modules\Payment\App\Application\Services;

use Modules\Payment\App\Domain\DTO\PaymentRequestDto;

class PayPalService extends AbstractGatewayService
{
    public function pay(PaymentRequestDto $request): array
    {
        return ['status' => 'success', 'redirect_url' => 'https://paypal.example.com', 'data' => []];
    }

    public function validateTransaction(string $validationId): array
    {
        return ['status' => 'completed', 'data' => ['transaction_id' => $validationId]];
    }

    public function refund(string $transactionId, float $amount): array
    {
        return ['status' => 'refunded', 'data' => []];
    }
}
