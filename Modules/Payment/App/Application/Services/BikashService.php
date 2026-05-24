<?php
namespace Modules\Payment\App\Application\Services;

use Modules\Payment\App\Domain\DTO\PaymentRequestDto;

class BikashService extends AbstractGatewayService
{
    protected string $appKey;
    protected string $appSecret;
    protected string $username;
    protected string $password;
    protected bool $isSandbox;
    protected string $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->appKey = config('payment.gateways.bikash.app_key') ?? '';
        $this->appSecret = config('payment.gateways.bikash.app_secret') ?? '';
        $this->username = config('payment.gateways.bikash.username') ?? '';
        $this->password = config('payment.gateways.bikash.password') ?? '';
        $this->isSandbox = config('payment.gateways.bikash.sandbox', true);
        
        $this->baseUrl = $this->isSandbox 
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta' 
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    protected function getToken(): string
    {
        // In production, you should cache this token for its lifetime (3600 seconds)
        $response = $this->sendRequest('POST', $this->baseUrl . '/tokenized/checkout/token/grant', [
            'headers' => [
                'username' => $this->username,
                'password' => $this->password,
            ],
            'json' => [
                'app_key' => $this->appKey,
                'app_secret' => $this->appSecret,
            ]
        ]);

        if (isset($response['id_token'])) {
            return $response['id_token'];
        }

        throw new \Modules\Payment\App\Domain\Exceptions\PaymentFailedException('Failed to get bKash token');
    }

    public function pay(\Modules\Payment\App\Domain\DTO\PaymentRequestDto $request): array
    {
        $token = $this->getToken();
        $trxId = $request->transactionId;

        $response = $this->sendRequest('POST', $this->baseUrl . '/tokenized/checkout/create', [
            'headers' => [
                'Authorization' => $token,
                'X-APP-Key' => $this->appKey,
            ],
            'json' => [
                'mode' => '0011', // 0011 for checkout
                'payerReference' => (string) $request->customerId,
                'callbackURL' => $request->mode === 'web' ? url('/payment/validate?gateway=bikash') : url('/api/v1/payment/validate?gateway=bikash'),
                'amount' => $request->amount,
                'currency' => 'BDT',
                'intent' => 'sale',
                'merchantInvoiceNumber' => 'INV-' . $request->invoiceId
            ]
        ]);

        if (isset($response['bkashURL'])) {
            return [
                'status' => 'success',
                'redirect_url' => $response['bkashURL'],
                'data' => [
                    'paymentID' => $response['paymentID'],
                    'tran_id' => $response['paymentID'] // Use paymentID as the primary transaction_id in our DB
                ]
            ];
        }

        throw new \Modules\Payment\App\Domain\Exceptions\PaymentFailedException('Failed to initialize bKash payment: ' . ($response['statusMessage'] ?? 'Unknown'));
    }

    public function validateTransaction(string $validationId): array
    {
        $token = $this->getToken();

        // 1. Try to Execute Payment
        $response = $this->sendRequest('POST', $this->baseUrl . '/tokenized/checkout/execute', [
            'headers' => [
                'Authorization' => $token,
                'X-APP-Key' => $this->appKey,
            ],
            'json' => [
                'paymentID' => $validationId
            ]
        ]);

        // If execute succeeded
        if (isset($response['statusCode']) && $response['statusCode'] === '0000' && $response['transactionStatus'] === 'Completed') {
            return $this->buildCompletedResponse($validationId, $response);
        }

        // 2. If execute failed with "already executed" or "invalid state", query the payment status instead
        $alreadyExecutedCodes = ['2056', '2062', '2117'];
        if (isset($response['statusCode']) && in_array($response['statusCode'], $alreadyExecutedCodes)) {
            return $this->queryPaymentStatus($validationId);
        }

        return [
            'status' => 'failed',
            'data' => [],
            'raw' => $response
        ];
    }

    /**
     * Query bKash for the final status of a payment (fallback when /execute was already called).
     */
    protected function queryPaymentStatus(string $paymentId): array
    {
        $token = $this->getToken();

        $response = $this->sendRequest('POST', $this->baseUrl . '/tokenized/checkout/payment/status', [
            'headers' => [
                'Authorization' => $token,
                'X-APP-Key' => $this->appKey,
            ],
            'json' => [
                'paymentID' => $paymentId
            ]
        ]);

        if (isset($response['statusCode']) && $response['statusCode'] === '0000' && $response['transactionStatus'] === 'Completed') {
            return $this->buildCompletedResponse($paymentId, $response);
        }

        return [
            'status' => 'failed',
            'data' => [],
            'raw' => $response
        ];
    }

    /**
     * Build a standardized completed response array.
     */
    protected function buildCompletedResponse(string $paymentId, array $response): array
    {
        return [
            'status' => 'completed',
            'data' => [
                'transaction_id' => $paymentId,
                'bank_trx_id' => $response['trxID'] ?? null,
                'val_id' => $paymentId,
                'invoice_id' => $response['merchantInvoiceNumber'] ?? null
            ],
            'raw' => $response
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        return ['status' => 'refunded', 'data' => []];
    }
}
