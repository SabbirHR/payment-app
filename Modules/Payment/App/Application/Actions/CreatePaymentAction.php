<?php
namespace Modules\Payment\App\Application\Actions;

use Modules\Payment\App\Domain\DTO\PaymentRequestDto;
use Modules\Payment\App\Domain\DTO\PaymentResponseDto;
use Modules\Payment\App\Domain\Contracts\PaymentGatewayInterface;

class CreatePaymentAction
{
    protected PaymentGatewayInterface $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Execute the payment creation.
     */
    public function execute(PaymentRequestDto $request): PaymentResponseDto
    {
        // Delegate to the gateway and wrap response in a DTO.
        $gatewayResponse = $this->gateway->pay($request);
        return new PaymentResponseDto($gatewayResponse);
    }
}
