<?php
namespace Modules\Payment\App\Domain\DTO;

class RefundDto
{
    public string $transactionId;
    public float $amount;
    public string $reason;

    public function __construct(string $transactionId, float $amount, string $reason = '')
    {
        $this->transactionId = $transactionId;
        $this->amount = $amount;
        $this->reason = $reason;
    }
}
