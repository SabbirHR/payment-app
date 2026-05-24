<?php
namespace Modules\Payment\App\Domain\Mappers;

use Modules\Payment\App\Domain\DTO\PaymentResponseDto;

class GatewayResponseMapper
{
    public static function map(array $rawResponse): PaymentResponseDto
    {
        return new PaymentResponseDto($rawResponse);
    }
}
