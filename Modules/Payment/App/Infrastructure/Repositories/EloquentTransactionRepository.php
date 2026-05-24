<?php
namespace Modules\Payment\App\Infrastructure\Repositories;

use Modules\Payment\App\Domain\Contracts\TransactionRepository;
use Modules\Payment\App\Domain\Models\PaymentTransaction;

class EloquentTransactionRepository implements TransactionRepository
{
    public function create(array $data)
    {
        return PaymentTransaction::create($data);
    }

    public function findByTransactionId(string $transactionId)
    {
        return PaymentTransaction::where('transaction_id', $transactionId)->first();
    }

    public function updateStatus(string $transactionId, string $status, ?array $gatewayResponse = null)
    {
        $transaction = $this->findByTransactionId($transactionId);
        if ($transaction) {
            $data = ['status' => $status];
            if ($gatewayResponse !== null) {
                $data['gateway_response'] = $gatewayResponse;
            }
            $transaction->update($data);
            return $transaction;
        }
        return null;
    }
}
