<?php
namespace Modules\Payment\App\Domain\DTO;

class PaymentResponseDto
{
    public array $data;
    public string $status;
    public string $redirectUrl;

    public function __construct(array $gatewayResponse)
    {
        $this->data = $gatewayResponse['data'] ?? [];
        $this->status = $gatewayResponse['status'] ?? 'unknown';
        $this->redirectUrl = $gatewayResponse['redirect_url'] ?? '';
    }
}
