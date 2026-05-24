<?php
namespace Modules\Payment\App\Application\Services;

use Modules\Payment\App\Domain\DTO\PaymentRequestDto;
use Modules\Payment\App\Domain\Exceptions\PaymentFailedException;

class ShurjoPayService extends AbstractGatewayService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $prefix;
    protected bool $isSandbox;

    public function __construct()
    {
        parent::__construct();

        $this->username  = config('payment.gateways.shurjopay.username') ?? '';
        $this->password  = config('payment.gateways.shurjopay.password') ?? '';
        $this->prefix    = config('payment.gateways.shurjopay.prefix') ?? 'sp';
        $this->isSandbox = (bool) config('payment.gateways.shurjopay.sandbox', true);

        $this->baseUrl = $this->isSandbox
            ? 'https://sandbox.shurjopayment.com'
            : 'https://engine.shurjopayment.com';
    }

    /**
     * Authenticate and get token
     */
    protected function getToken(): array
    {
        $response = $this->sendRequest('POST', $this->baseUrl . '/api/get_token', [
            'json' => [
                'username' => $this->username,
                'password' => $this->password,
            ],
            'http_errors' => false,
        ]);

        if (empty($response['token']) || empty($response['store_id'])) {
            throw new PaymentFailedException('ShurjoPay Authentication failed: ' . json_encode($response));
        }

        return $response;
    }

    public function pay(PaymentRequestDto $request): array
    {
        // 1. Get Token
        $auth = $this->getToken();
        $token = $auth['token'];
        $storeId = $auth['store_id'];
        $executeUrl = $auth['execute_url'] ?? ($this->baseUrl . '/api/secret-pay');

        // 2. Prepare Payment Payload
        $payload = [
            'prefix'             => $this->prefix,
            'token'              => $token,
            'store_id'           => $storeId,
            'return_url'         => $request->mode === 'web' ? url('/payment/validate?gateway=shurjopay') : url('/api/v1/payment/validate?gateway=shurjopay'),
            'cancel_url'         => $request->mode === 'web' ? url('/payment/validate?gateway=shurjopay&status=cancelled&val_id=' . $request->transactionId) : url('/api/v1/payment/validate?gateway=shurjopay&status=cancelled&val_id=' . $request->transactionId),
            'amount'             => number_format($request->amount, 2, '.', ''),
            'order_id'           => $request->transactionId,
            'currency'           => 'BDT',
            'customer_name'      => $request->customerName ?? 'Test Customer',
            'customer_address'   => $request->customerAddress ?? 'Dhaka, Bangladesh',
            'customer_phone'     => $request->customerPhone ?? '01700000000',
            'customer_city'      => 'Dhaka',
            'customer_post_code' => '1212',
            'client_ip'          => request()->ip() ?? '127.0.0.1',
        ];

        // 3. Request Checkout URL
        $response = $this->sendRequest('POST', $executeUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'json' => $payload,
            'http_errors' => false,
        ]);

        if (empty($response['checkout_url'])) {
            throw new PaymentFailedException('ShurjoPay init failed: ' . json_encode($response));
        }

        return [
            'status'       => 'success',
            'redirect_url' => $response['checkout_url'],
            'data'         => [
                'tran_id'     => $request->transactionId,
                'sp_order_id' => $response['sp_order_id'] ?? null,
                'amount'      => $payload['amount'],
            ],
        ];
    }

    public function validateTransaction(string $validationId): array
    {
        // 1. Get Token
        $auth = $this->getToken();
        $token = $auth['token'];

        // 2. Call Verification API
        // Typically /api/verification accepts an array and returns an array of records
        $response = $this->sendRequest('POST', $this->baseUrl . '/api/verification', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'json' => [
                'order_id' => $validationId,
            ],
            'http_errors' => false,
        ]);

        // Response is usually an array of objects
        $data = $response[0] ?? $response;

        if (isset($data['sp_code']) && $data['sp_code'] == '1000') {
            return [
                'status' => 'completed',
                'data'   => [
                    'transaction_id' => $data['customer_order_id'] ?? $validationId, // Your original transactionId
                    'val_id'         => $validationId,
                    'invoice_id'     => $data['customer_order_id'] ?? null,
                ],
                'raw'    => $response,
            ];
        }

        return [
            'status' => 'failed',
            'data'   => [
                'transaction_id' => $data['customer_order_id'] ?? null,
            ],
            'raw'    => $response,
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        return [
            'status' => 'not_supported',
            'data'   => [],
        ];
    }
}
