<?php
namespace Modules\Payment\App\Domain\Contracts;

interface TransactionRepository
{
    public function create(array $data);
    public function findByTransactionId(string $transactionId);
    public function updateStatus(string $transactionId, string $status);
}
