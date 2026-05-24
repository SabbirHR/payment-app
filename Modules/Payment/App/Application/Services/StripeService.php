<?php

namespace Modules\Payment\App\Application\Services;

use Modules\Payment\App\Domain\Contracts\PaymentGatewayInterface;

class StripeService implements PaymentGatewayInterface
{
    public function amount(float $amount): self { /* set amount */ return $this; }
    public function pay(array $options = []): array { return ['status' => 'success', 'redirect_url' => 'https://example.com']; }
    public function validateTransaction(string $valId): array { return ['status' => 'validated']; }
    public function refund(string $transactionId, float $amount): bool { return true; }
}

?>
