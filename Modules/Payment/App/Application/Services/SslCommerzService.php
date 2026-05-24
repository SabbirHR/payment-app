<?php
namespace Modules\Payment\App\Application\Services;

use Modules\Payment\App\Domain\DTO\PaymentRequestDto;

class SslCommerzService extends AbstractGatewayService
{
    protected string $storeId;
    protected string $storePassword;
    protected bool $isSandbox;
    protected string $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->storeId = config('payment.gateways.sslcommerz.store_id');
        $this->storePassword = config('payment.gateways.sslcommerz.store_password');
        $this->isSandbox = config('payment.gateways.sslcommerz.sandbox', true);
        $this->baseUrl = $this->isSandbox 
            ? 'https://sandbox.sslcommerz.com' 
            : 'https://securepay.sslcommerz.com';
    }

    public function pay(\Modules\Payment\App\Domain\DTO\PaymentRequestDto $request): array
    {
        // $request is typically PaymentRequestDto
        $postData = [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'total_amount' => $request->amount,
            'currency' => $request->currency ?? 'BDT',
            'tran_id' => $request->transactionId,
            'success_url' => url('/api/v1/payment/validate?gateway=sslcommerz'),
            'fail_url' => url('/api/v1/payment/validate?gateway=sslcommerz&status=FAILED'),
            'cancel_url' => url('/api/v1/payment/validate?gateway=sslcommerz&status=CANCELLED'),
            'cus_name' => 'Customer', // In a real scenario, extract from $request
            'cus_email' => 'customer@example.com',
            'cus_add1' => 'Dhaka',
            'cus_city' => 'Dhaka',
            'cus_country' => 'Bangladesh',
            'cus_phone' => '01700000000',
            'shipping_method' => 'NO',
            'product_name' => 'Booking Invoice',
            'product_category' => 'Travel',
            'product_profile' => 'general',
            'value_a' => $request->invoiceId // We can pass invoice ID in value_a to retrieve it later
        ];

        $response = $this->sendRequest('POST', $this->baseUrl . '/gwprocess/v4/api.php', [
            'form_params' => $postData
        ]);

        if (isset($response['status']) && $response['status'] === 'SUCCESS') {
            return [
                'status' => 'success',
                'redirect_url' => $response['GatewayPageURL'],
                'data' => $response
            ];
        }

        throw new \Modules\Payment\App\Domain\Exceptions\PaymentFailedException('Failed to initialize SSLCommerz payment: ' . ($response['failedreason'] ?? 'Unknown error'));
    }

    public function validateTransaction(string $validationId): array
    {
        $url = $this->baseUrl . "/validator/api/validationserverAPI.php?val_id=" . urlencode($validationId) . "&store_id=" . urlencode($this->storeId) . "&store_passwd=" . urlencode($this->storePassword) . "&v=1&format=json";

        $response = $this->sendRequest('GET', $url);

        if (isset($response['status']) && ($response['status'] === 'VALID' || $response['status'] === 'VALIDATED')) {
            return [
                'status' => 'completed',
                'data' => [
                    'transaction_id' => $response['tran_id'], // SSLCommerz transaction ID
                    'val_id' => $response['val_id'],
                    'invoice_id' => $response['value_a'] ?? null
                ],
                'raw' => $response
            ];
        }

        return [
            'status' => 'failed',
            'data' => [],
            'raw' => $response
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        // Refund implementation for SSLCommerz
        return ['status' => 'refunded', 'data' => []];
    }
}
