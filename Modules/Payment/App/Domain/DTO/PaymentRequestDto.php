<?php
namespace Modules\Payment\App\Domain\DTO;

class PaymentRequestDto
{
    public string $currency;
    public float $amount;
    public string $description;
    public ?int $invoiceId;
    public ?int $customerId;
    public string $transactionId;
    public string $mode;

    public function __construct(string $currency, float $amount, string $description = '', ?int $invoiceId = null, ?int $customerId = null, string $transactionId = '', string $mode = 'api')
    {
        $this->currency = $currency;
        $this->amount = $amount;
        $this->description = $description;
        $this->invoiceId = $invoiceId;
        $this->customerId = $customerId;
        $this->transactionId = $transactionId ?: uniqid('txn_');
        $this->mode = $mode;
    }
}
