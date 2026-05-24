<?php
namespace Modules\Payment\App\Application\Services;

use Modules\Payment\App\Domain\Contracts\PaymentGatewayFactory;
use Modules\Payment\App\Domain\Contracts\TransactionRepository;
use Modules\Payment\App\Domain\DTO\PaymentRequestDto;
use Modules\Payment\App\Domain\DTO\PaymentResponseDto;
use Illuminate\Support\Facades\Log;
class PaymentService
{
    protected $gatewayFactory;
    protected $repository;
    protected $currentGateway;
    protected $amount;
    protected $invoiceId;
    protected $customerId;
    protected $currency = 'BDT';
    protected $description = '';

    public function __construct(PaymentGatewayFactory $gatewayFactory, TransactionRepository $repository)
    {
        $this->gatewayFactory = $gatewayFactory;
        $this->repository = $repository;
    }

    public function gateway(string $gatewayName): self
    {
        $this->currentGateway = $this->gatewayFactory->make($gatewayName);
        return $this;
    }

    public function amount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function currency(string $currency): self
    {
        $this->currency = strtoupper($currency);
        return $this;
    }

    public function invoice(int $invoiceId): self
    {
        $this->invoiceId = $invoiceId;
        return $this;
    }

    public function customer($customerData): self
    {
        // Automatically sync host app's user (or array data) into our independent table
        $hostUserId = is_object($customerData) ? ($customerData->id ?? null) : ($customerData['id'] ?? null);
        $name = is_object($customerData) ? ($customerData->name ?? 'Guest') : ($customerData['name'] ?? 'Guest');
        $email = is_object($customerData) ? ($customerData->email ?? null) : ($customerData['email'] ?? null);
        $phone = is_object($customerData) ? ($customerData->phone ?? null) : ($customerData['phone'] ?? null);

        $paymentCustomer = \Modules\Payment\App\Domain\Models\PaymentCustomer::firstOrCreate(
            ['host_user_id' => $hostUserId, 'email' => $email],
            ['name' => $name, 'phone' => $phone]
        );

        $this->customerId = $paymentCustomer->id;
        return $this;
    }

    public function pay(): PaymentResponseDto
    {
        $transactionId = uniqid('txn_');

        $requestDto = new PaymentRequestDto(
            $this->currency,
            $this->amount,
            $this->description,
            $this->invoiceId,
            $this->customerId,
            $transactionId
        );

        $gatewayResponse = $this->currentGateway->pay($requestDto);

        // Auto-create invoice if an external invoiceId was not provided
        $invoice = \Modules\Payment\App\Domain\Models\PaymentInvoice::create([
            'customer_id' => $this->customerId,
            'invoice_number' => uniqid('INV-'),
            'total_amount' => $this->amount,
            'status' => 'unpaid',
        ]);

        // Record pending transaction linked to the invoice
        $this->repository->create([
            'invoice_id' => $invoice->id,
            'transaction_id' => $gatewayResponse['data']['tran_id'] ?? $transactionId,
            'gateway' => class_basename($this->currentGateway),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => 'pending',
        ]);

        return new PaymentResponseDto($gatewayResponse);
    }

    public function validateTransaction(string $valId)
    {
        $response = $this->currentGateway->validateTransaction($valId);
        
        if (!empty($response['data']['transaction_id'])) {
            if ($response['status'] === 'completed') {
                // Map gateway 'completed' → our 'paid' status
                $transaction = $this->repository->updateStatus($response['data']['transaction_id'], 'paid');
                
                // Mark invoice as paid (if relationship exists)
                if ($transaction && $transaction->invoice) {
                    $transaction->invoice->markAsPaid();
                }
            } elseif ($response['status'] === 'failed') {
                $transaction = $this->repository->updateStatus($response['data']['transaction_id'], 'failed');
                
                if ($transaction && $transaction->invoice) {
                    $transaction->invoice->markAsFailed();
                }
            }
        }
        
        return $response;
    }

    /**
     * Update a transaction status directly (used for failed/cancelled callbacks
     * where the gateway doesn't need server-side verification).
     */
    public function updateTransactionStatus(string $valId, string $status): void
    {
        $transaction = $this->repository->findByTransactionId($valId);
        Log::info('transaction'. $transaction);
        if ($transaction) {
            $transaction->updateStatusFromPending($status);

            // Also update the related invoice
            if ($transaction->invoice) {
                if ($status === 'failed') {
                    $transaction->invoice->markAsFailed();
                } elseif ($status === 'cancelled') {
                    $transaction->invoice->update(['status' => 'cancelled']);
                }
            }
        }
    }
}
