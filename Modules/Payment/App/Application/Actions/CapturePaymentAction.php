<?php
namespace Modules\Payment\App\Application\Actions;

use Modules\Payment\App\Domain\DTO\PaymentRequestDto;
use Modules\Payment\App\Domain\DTO\PaymentResponseDto;
use Modules\Payment\App\Domain\Contracts\PaymentGatewayInterface;

class CapturePaymentAction
{
    protected PaymentGatewayInterface $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function execute(PaymentRequestDto $request): PaymentResponseDto
    {
        // Implement capture logic via gateway
        $gatewayResponse = $this->gateway->pay($request); // placeholder – replace with actual capture method
        return new PaymentResponseDto($gatewayResponse);
    }
}
