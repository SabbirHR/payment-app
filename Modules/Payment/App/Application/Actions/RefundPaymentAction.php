<?php
namespace Modules\Payment\App\Application\Actions;

use Modules\Payment\App\Domain\Contracts\PaymentGatewayInterface;
use Modules\Payment\App\Domain\DTO\RefundDto;

class RefundPaymentAction
{
    protected PaymentGatewayInterface $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function execute(RefundDto $request): array
    {
        return $this->gateway->refund($request->transactionId, $request->amount);
    }
}
