<?php
namespace Modules\Payment\App\Application\Services;

use Modules\Payment\App\Domain\DTO\PaymentRequestDto;
use Modules\Payment\App\Domain\Exceptions\PaymentFailedException;

/**
 * AamarPay – sandbox gateway (very similar to bKash).
 *
 * Flow:
 *   1️⃣ Build a form‑urlencoded payload (store_id, amount, order_id, …).
 *   2️⃣ POST to sandbox `/request`.
 *   3️⃣ Sandbox returns JSON with `payment_url`.
 *   4️⃣ Return that URL as `redirect_url` for the front‑end.
 *   5️⃣ After payment the gateway redirects back to our
 *      `/api/v1/payment/validate?gateway=aamarpay&tran_id=…`.
 *   6️⃣ We verify the transaction with a GET to `/verify_payment/{tran_id}`.
 *
 * The method signatures match those of BikashService, so the
 * PaymentController can call any gateway interchangeably.
 */
class AamarPayService extends AbstractGatewayService
{
    // -----------------------------------------------------------------
    // Configuration values – read from config/payment.php
    // -----------------------------------------------------------------
    protected string $baseUrl;
    protected string $storeId;
    protected string $signatureKey;
    protected bool   $isSandbox;

    public function __construct()
    {
        parent::__construct();

        $this->storeId      = config('payment.gateways.aamarpay.store_id', '');
        $this->signatureKey = config('payment.gateways.aamarpay.signature_key', '');
        $this->isSandbox    = (bool) config('payment.gateways.aamarpay.sandbox', true);

        // Hardcoded like BikashService – no env needed for base URL
        $this->baseUrl = $this->isSandbox
            ? 'https://sandbox.aamarpay.com'
            : 'https://secure.aamarpay.com';
    }

    // -----------------------------------------------------------------
    // Helper: generate the MD5 signature required by AamarPay
    // -----------------------------------------------------------------
    protected function generateSignature(array $components): string
    {
        // The spec says: md5( store_id . amount . order_id . currency . success_url . fail_url . cancel_url . signature_key )
        $raw = implode('', $components) . $this->signatureKey;
        return strtolower(md5($raw));
    }

    // -----------------------------------------------------------------
    // PUBLIC API: initiate a payment
    // -----------------------------------------------------------------
    public function pay(PaymentRequestDto $request): array
    {
        // AamarPay expects a **form‑urlencoded** payload.
        $payload = [
            'store_id'       => $this->storeId,
            'tran_id'        => $request->transactionId,                     // unique per checkout
            'amount'         => number_format($request->amount, 2, '.', ''), // e.g. "25.00"
            'currency'       => 'BDT',
            'desc'           => $request->description ?: 'Payment',
            'cus_name'       => 'Test Customer',
            'cus_email'      => 'test@example.com',
            'cus_add1'       => 'Dhaka',
            'cus_add2'       => 'Dhaka',
            'cus_city'       => 'Dhaka',
            'cus_state'      => 'Dhaka',
            'cus_postcode'   => '1000',
            'cus_country'    => 'Bangladesh',
            'cus_phone'      => '01770618575',
            'signature_key'  => $this->signatureKey,
            'success_url'    => url('/api/v1/payment/validate?gateway=aamarpay'),
            'fail_url'       => url('/api/v1/payment/validate?gateway=aamarpay&status=failed&mer_txnid=' . $request->transactionId),
            'cancel_url'     => url('/api/v1/payment/validate?gateway=aamarpay&status=cancelled&mer_txnid=' . $request->transactionId),
            'type'           => 'json',  // tells sandbox to return JSON instead of HTML
        ];

        // AamarPay sandbox: signature_key is already in the payload (no separate hash needed).
        // The endpoint for JSON responses is /jsonpost.php (expects JSON body)
        $response = $this->sendRequest('POST', $this->baseUrl . '/jsonpost.php', [
            'json'        => $payload,
            'http_errors' => false,
        ]);

        // Sandbox JSON response:
        // Success: { "result": "true", "payment_url": "https://sandbox.aamarpay.com/..." }
        // Failure: { "result": "false", ... }
        if (
            (isset($response['result']) && $response['result'] === 'true') ||
            !empty($response['payment_url'])
        ) {
            return [
                'status'       => 'success',
                'redirect_url' => $response['payment_url'],
                'data'         => [
                    'tran_id' => $payload['tran_id'],
                    'amount'  => $payload['amount'],
                ],
            ];
        }

        throw new PaymentFailedException(
            'AamarPay init failed: ' . json_encode($response)
        );
    }

    // -----------------------------------------------------------------
    // PUBLIC API: validate (called from PaymentController after callback)
    // -----------------------------------------------------------------
    public function validateTransaction(string $validationId): array
    {
        // The sandbox uses GET /api/v1/trxcheck/request.php
        $url = $this->baseUrl . '/api/v1/trxcheck/request.php';

        $response = $this->sendRequest('GET', $url, [
            'query' => [
                'request_id'    => $validationId,
                'store_id'      => $this->storeId,
                'signature_key' => $this->signatureKey,
                'type'          => 'json',
            ],
            'http_errors' => false,
        ]);

        // Successful verification looks like:
        // { "pay_status":"Successful", "mer_txnid":"TR0011XYZ", "amount":"95.00", ... }
        if (isset($response['pay_status']) && $response['pay_status'] === 'Successful') {
            return [
                'status' => 'completed',
                'data'   => [
                    'transaction_id' => $response['mer_txnid'] ?? $validationId,
                    'val_id'         => $validationId,
                    'invoice_id'     => $response['mer_txnid'] ?? null,
                ],
                'raw'    => $response,
            ];
        }

        // Anything else is a failure
        return [
            'status' => 'failed',
            'data'   => [],
            'raw'    => $response,
        ];
    }

    // -----------------------------------------------------------------
    // PUBLIC API: refund (sandbox does not support refunds – placeholder)
    // -----------------------------------------------------------------
    public function refund(string $transactionId, float $amount): array
    {
        // AamarPay sandbox currently has no refund endpoint.
        // We return a neutral response so the rest of the system can continue.
        return [
            'status' => 'not_supported',
            'data'   => [],
        ];
    }
}
