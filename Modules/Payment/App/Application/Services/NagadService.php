<?php
namespace Modules\Payment\App\Application\Services;

use Modules\Payment\App\Domain\DTO\PaymentRequestDto;
use Modules\Payment\App\Domain\Exceptions\PaymentFailedException;

class NagadService extends AbstractGatewayService
{
    protected string $merchantId;
    protected string $merchantNumber;
    protected string $publicKey;
    protected string $privateKey;
    protected bool $isSandbox;
    protected string $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->merchantId = config('payment.gateways.nagad.merchant_id', '');
        $this->merchantNumber = config('payment.gateways.nagad.merchant_number', '');
        $this->publicKey = config('payment.gateways.nagad.public_key', '');
        $this->privateKey = config('payment.gateways.nagad.private_key', '');
        $this->isSandbox = config('payment.gateways.nagad.sandbox', true);

        $this->baseUrl = $this->isSandbox
            ? 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs'
            : 'https://api.mynagad.com/api/dfs';
    }

    public function pay(PaymentRequestDto $request): array
    {
        $orderId = $request->transactionId;
        $dateTime = now()->format('YmdHis');

        // Step 1: Initialize Payment
        $sensitiveData = [
            'merchantId' => $this->merchantId,
            'datetime' => $dateTime,
            'orderId' => $orderId,
            'challenge' => $this->generateRandomString(40),
        ];

        $sensitiveDataEncrypted = $this->encryptWithPublicKey(json_encode($sensitiveData));
        $signature = $this->signWithPrivateKey(json_encode($sensitiveData));

        $initResponse = $this->sendRequest('POST', $this->baseUrl . '/check-out/initialize/' . $this->merchantId . '/' . $orderId, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-KM-Api-Version' => 'v-0.2.0',
                'X-KM-IP-V4' => request()->ip() ?? '127.0.0.1',
                'X-KM-Client-Type' => 'PC_WEB',
            ],
            'json' => [
                'accountNumber' => $this->merchantNumber,
                'dateTime' => $dateTime,
                'sensitiveData' => $sensitiveDataEncrypted,
                'signature' => $signature,
            ]
        ]);

        if (!isset($initResponse['sensitiveData']) || !isset($initResponse['signature'])) {
            throw new PaymentFailedException('Nagad initialization failed: ' . json_encode($initResponse));
        }

        // Decrypt the response
        $decryptedData = json_decode($this->decryptWithPrivateKey($initResponse['sensitiveData']), true);

        if (!isset($decryptedData['paymentReferenceId']) || !isset($decryptedData['challenge'])) {
            throw new PaymentFailedException('Invalid Nagad init response');
        }

        // Step 2: Complete Payment
        $paymentDetails = [
            'merchantId' => $this->merchantId,
            'orderId' => $orderId,
            'currencyCode' => '050', // BDT
            'amount' => (string) $request->amount,
            'challenge' => $decryptedData['challenge'],
        ];

        $paymentDetailsEncrypted = $this->encryptWithPublicKey(json_encode($paymentDetails));
        $paymentSignature = $this->signWithPrivateKey(json_encode($paymentDetails));

        $completeResponse = $this->sendRequest('POST', $this->baseUrl . '/check-out/complete/' . $decryptedData['paymentReferenceId'], [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-KM-Api-Version' => 'v-0.2.0',
                'X-KM-IP-V4' => request()->ip() ?? '127.0.0.1',
                'X-KM-Client-Type' => 'PC_WEB',
            ],
            'json' => [
                'sensitiveData' => $paymentDetailsEncrypted,
                'signature' => $paymentSignature,
                'merchantCallbackURL' => url('/api/v1/payment/validate?gateway=nagad'),
            ]
        ]);

        if (isset($completeResponse['callBackUrl'])) {
            return [
                'status' => 'success',
                'redirect_url' => $completeResponse['callBackUrl'],
                'data' => [
                    'tran_id' => $orderId,
                    'payment_ref' => $decryptedData['paymentReferenceId'],
                ]
            ];
        }

        throw new PaymentFailedException('Nagad payment completion failed: ' . json_encode($completeResponse));
    }

    public function validateTransaction(string $validationId): array
    {
        // Nagad sends payment_ref_id in the callback. We use it to verify.
        $response = $this->sendRequest('GET', $this->baseUrl . '/verify/payment/' . $validationId);

        if (isset($response['status']) && $response['status'] === 'Success') {
            return [
                'status' => 'completed',
                'data' => [
                    'transaction_id' => $response['orderId'] ?? $validationId,
                    'val_id' => $validationId,
                    'invoice_id' => $response['orderId'] ?? null,
                ],
                'raw' => $response,
            ];
        }

        return [
            'status' => 'failed',
            'data' => [],
            'raw' => $response,
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        return ['status' => 'refunded', 'data' => []];
    }

    // ──────────────────────────────────────────────
    // Crypto Helpers (RSA encryption/decryption)
    // ──────────────────────────────────────────────

    protected function encryptWithPublicKey(string $data): string
    {
        $pgPublicKey = "-----BEGIN PUBLIC KEY-----\n" . $this->publicKey . "\n-----END PUBLIC KEY-----";
        $keyResource = openssl_pkey_get_public($pgPublicKey);

        if (!$keyResource) {
            throw new PaymentFailedException('Invalid Nagad public key');
        }

        openssl_public_encrypt($data, $encrypted, $keyResource);

        return base64_encode($encrypted);
    }

    protected function signWithPrivateKey(string $data): string
    {
        $merchantPrivateKey = "-----BEGIN RSA PRIVATE KEY-----\n" . $this->privateKey . "\n-----END RSA PRIVATE KEY-----";
        $keyResource = openssl_pkey_get_private($merchantPrivateKey);

        if (!$keyResource) {
            throw new PaymentFailedException('Invalid Nagad private key');
        }

        openssl_sign($data, $signature, $keyResource, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    protected function decryptWithPrivateKey(string $data): string
    {
        $merchantPrivateKey = "-----BEGIN RSA PRIVATE KEY-----\n" . $this->privateKey . "\n-----END RSA PRIVATE KEY-----";
        $keyResource = openssl_pkey_get_private($merchantPrivateKey);

        if (!$keyResource) {
            throw new PaymentFailedException('Invalid Nagad private key for decryption');
        }

        openssl_private_decrypt(base64_decode($data), $decrypted, $keyResource);

        return $decrypted;
    }

    protected function generateRandomString(int $length = 40): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
